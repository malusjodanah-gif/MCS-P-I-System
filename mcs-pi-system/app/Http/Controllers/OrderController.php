<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['supplier', 'user', 'stockItem'])
            ->orderByDesc('ge_number')
            ->orderBy('order_line_id')
            ->get()
            ->groupBy('ge_number');
        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('supplier')->get();
        $items = Item::orderBy('item')->get();
        $nextGe = (Order::max('ge_number') ?? 1000) + 1;
        $branches = Branch::orderBy('branch_id')->get();
        return view('orders.create', compact('suppliers', 'items', 'nextGe', 'branches'));
    }

    public function store(Request $request)
    {
        $header = $request->validate([
            'ge_number' => 'nullable|integer|min:1|max:2147483647',
            'inventory_flag' => 'required|in:STOCK,NON-STOCK',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'branch_id' => 'nullable|integer|exists:branches,branch_id',
            'order_date' => 'nullable|date',
            'account_code' => 'nullable|string|max:50',
        ]);
        $bulk = $request->has('lines');
        if ($bulk) {
            $request->validate(['lines' => 'required|array|min:1|max:100', 'lines.*' => 'array']);
        }
        $rawLines = $bulk ? $request->input('lines') : [$request->only('item_id', 'item', 'quantity', 'unit_cost')];
        $prepared = [];
        foreach ($rawLines as $index => $raw) {
            $blank = collect(['item_id', 'item', 'quantity', 'unit_cost'])->every(
                fn ($key) => !isset($raw[$key]) || (is_string($raw[$key]) && trim($raw[$key]) === '')
            );
            if ($bulk && $blank) {
                continue;
            }
            $prefix = $bulk ? "lines.$index." : '';
            $rules = [
                $prefix.'item_id' => $header['inventory_flag'] === 'STOCK' ? 'required|integer|exists:items,item_id' : 'nullable',
                $prefix.'item' => $header['inventory_flag'] === 'NON-STOCK' ? 'required|string|max:255' : 'nullable|string|max:255',
                $prefix.'quantity' => 'required|integer|min:1|max:2147483647',
                $prefix.'unit_cost' => 'required|numeric|min:0|max:99999999.9999',
            ];
            $validated = \Illuminate\Support\Facades\Validator::make($bulk ? ['lines' => [$index => $raw]] : $raw, $rules)->validate();
            $line = $bulk ? $validated['lines'][$index] : $validated;
            if ($header['inventory_flag'] === 'STOCK') {
                $item = Item::findOrFail($line['item_id']);
                $line['item'] = $item->item;
                $line['uom'] = $item->uom;
            } else {
                $line['item_id'] = null;
                $line['uom'] = null;
            }
            $line['unit_cost'] = round((float) $line['unit_cost'], 4);
            $line['total_cost'] = round((int) $line['quantity'] * (float) $line['unit_cost'], 2);
            if ($line['total_cost'] > 9999999999.99) {
                throw ValidationException::withMessages([$prefix.'unit_cost' => 'The line total exceeds the supported amount.']);
            }
            $prepared[] = $line;
        }
        if (!$prepared) {
            throw ValidationException::withMessages(['lines' => 'Enter at least one complete order line.']);
        }
        $branch = $header['branch_id'] ?? Auth::user()->branch_id ?? 201;
        $ge = DB::transaction(function () use ($request, $header, $branch, $prepared) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['mcs-ge-allocation']);
            }
            $ge = $header['ge_number'] ?? ((Order::max('ge_number') ?? 1000) + 1);
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(?)', [(int) $ge]);
            }
            $existing = Order::where('ge_number', $ge)->lockForUpdate()->get();
            $first = $existing->first();
            if ($first && ($existing->contains(fn ($line) => $line->status !== 'Pending')
                || $first->supplier_id != $header['supplier_id']
                || $first->inventory_flag !== $header['inventory_flag']
                || $first->branch_id != $branch
                || $first->account_code !== ($header['account_code'] ?? null)
                || ($request->filled('order_date') && $first->order_date->toDateString() !== \Illuminate\Support\Carbon::parse($header['order_date'])->toDateString()))) {
                throw ValidationException::withMessages(['ge_number' => 'Add lines only to a Pending GE with the same supplier, flag, branch, date and account code.']);
            }
            $date = $first ? $first->order_date : ($header['order_date'] ?? now()->toDateString());
            foreach ($prepared as $line) {
                Order::create([
                    'ge_number' => $ge, 'po_number' => null,
                    'inventory_flag' => $header['inventory_flag'], 'supplier_id' => $header['supplier_id'],
                    'branch_id' => $branch, 'order_date' => $date, 'user_id' => Auth::user()->user_id,
                    'account_code' => $header['account_code'] ?? null,
                    'item_id' => $line['item_id'], 'item' => $line['item'], 'uom' => $line['uom'],
                    'quantity' => $line['quantity'], 'unit_cost' => $line['unit_cost'],
                    'total_cost' => $line['total_cost'], 'status' => 'Pending',
                ]);
            }
            return $ge;
        }, 5);
        return redirect()->route('orders.index')->with('success', "GE #{$ge} saved (Pending). Status remains manual.");
    }

    public function edit(Order $order)
    {
        if ($order->status !== 'Pending' || $order->receipts()->exists()) {
            throw ValidationException::withMessages(['order' => 'Only unreceipted Pending lines can be edited.']);
        }
        return view('orders.edit', [
            'row' => $order, 'nextGe' => $order->ge_number,
            'suppliers' => Supplier::orderBy('supplier')->get(),
            'items' => Item::orderBy('item')->get(),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'item_id' => 'nullable|exists:items,item_id',
            'item' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
        ]);
        DB::transaction(function () use ($order, $data) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            if ($order->status !== 'Pending' || $order->receipts()->exists()) {
                throw ValidationException::withMessages(['order' => 'Only unreceipted Pending lines can be edited.']);
            }
            if ($order->inventory_flag === 'STOCK') {
                if (empty($data['item_id'])) {
                    throw ValidationException::withMessages(['item_id' => 'Select a stock item.']);
                }
                $item = Item::findOrFail($data['item_id']);
                $data['item'] = $item->item;
                $data['uom'] = $item->uom;
            } else {
                if (empty($data['item'])) {
                    throw ValidationException::withMessages(['item' => 'Enter the service or non-stock description.']);
                }
                $data['item_id'] = null;
            }
            $data['total_cost'] = round($data['quantity'] * (float) $data['unit_cost'], 2);
            $order->update($data);
        });
        return redirect()->route('orders.index')->with('success', 'GE line updated.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Order::STATUSES),
            'update_whole_ge' => 'nullable|boolean',
            'po_number' => 'nullable|integer|min:1|max:2147483647',
        ]);

        DB::transaction(function () use ($request, $order, $data) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(?)', [(int) $order->ge_number]);
            }
            if (!empty($data['po_number']) && $data['status'] !== 'Ordered') {
                throw ValidationException::withMessages(['po_number' => 'Assign a PO Number when setting the order to Ordered.']);
            }
            // Serialize PO assignment across different GEs before checking supplier ownership.
            if ($data['status'] === 'Ordered' && DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['mcs-po-assignment']);
            }
            $query = Order::query();
            $lines = ($request->boolean('update_whole_ge')
                ? $query->where('ge_number', $order->ge_number)
                : $query->whereKey($order->getKey()))->orderBy('order_line_id')->lockForUpdate()->get();
            foreach ($lines as $line) {
                $allowed = match ($line->status) {
                    'Pending' => ['Approved', 'Rejected', 'Cancelled'],
                    'Approved' => ['Ordered', 'Cancelled'],
                    'Ordered' => ['Received', 'Cancelled'],
                    'Received' => ['Backorder', 'Complete', 'Cancelled'],
                    'Backorder' => ['Received', 'Complete', 'Cancelled'],
                    'Complete' => ['Received'],
                    default => [],
                };
                if ($data['status'] !== $line->status && !in_array($data['status'], $allowed, true)) {
                    throw ValidationException::withMessages(['status' => "Cannot change {$line->status} to {$data['status']}."]);
                }
                if ($data['status'] === 'Ordered') {
                    $po = $data['po_number'] ?? $line->po_number ?? $line->ge_number;
                    if (Order::where('po_number', $po)->where('supplier_id', '!=', $line->supplier_id)->exists()) {
                        throw ValidationException::withMessages(['po_number' => 'This PO Number belongs to another supplier.']);
                    }
                    if ($line->receipts()->where('po_number', '!=', $po)->exists()) {
                        throw ValidationException::withMessages(['po_number' => 'This line already has receipts with a different PO Number.']);
                    }
                }
                $received = (int) $line->receipts()->sum('quantity_received');
                if ($data['status'] === 'Complete' && $received !== $line->quantity) {
                    throw ValidationException::withMessages(['status' => 'Receive the full ordered quantity before marking Complete.']);
                }
                if ($data['status'] === 'Backorder' && ($received <= 0 || $received >= $line->quantity)) {
                    throw ValidationException::withMessages(['status' => 'Backorder requires a partial receipt with quantity still outstanding.']);
                }
                if ($data['status'] === 'Received' && $received <= 0) {
                    throw ValidationException::withMessages(['status' => 'Record a receipt before marking Received.']);
                }
                if ($data['status'] === 'Cancelled' && $received > 0) {
                    throw ValidationException::withMessages(['status' => 'An order with receipts cannot be cancelled.']);
                }
            }
            foreach ($lines as $line) {
                $line->status = $data['status'];
                if ($line->status === 'Ordered') {
                    $line->po_number = $data['po_number'] ?? $line->po_number ?? $line->ge_number;
                }
                $line->save();
            }
        });

        return back()->with('success', 'Status updated to ' . $data['status']);
    }

    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            if (!in_array($order->status, ['Pending', 'Rejected', 'Cancelled'], true) || $order->receipts()->exists()) {
                throw ValidationException::withMessages(['status' => 'Only unreceipted Pending/Rejected/Cancelled lines can be deleted.']);
            }
            $order->delete();
        });
        return back()->with('success', 'Order line deleted.');
    }
}

