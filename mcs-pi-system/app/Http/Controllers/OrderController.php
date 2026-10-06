<?php

namespace App\Http\Controllers;

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
        return view('orders.create', compact('suppliers', 'items', 'nextGe'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ge_number' => 'nullable|integer|min:1|max:2147483647',
            'inventory_flag' => 'required|in:STOCK,NON-STOCK',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'account_code' => 'nullable|string|max:50',
            'item_id' => 'nullable|exists:items,item_id',
            'item' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        $requestedGe = $data['ge_number'] ?? null;
        $desc = $data['item'] ?? null;
        $uom = null;
        $itemId = $data['item_id'] ?? null;

        if ($data['inventory_flag'] === 'STOCK') {
            if (!$itemId) {
                return back()->withErrors(['item_id' => 'Item is required for STOCK orders'])->withInput();
            }
            $item = Item::findOrFail($itemId);
            $desc = $item->item;
            $uom = $item->uom;
        } else {
            $itemId = null;
            if (!$desc) {
                return back()->withErrors(['item' => 'Description required for NON-STOCK'])->withInput();
            }
        }

        $qty = (int) $data['quantity'];
        $unit = (float) $data['unit_cost'];

        $ge = DB::transaction(function () use ($requestedGe, $data, $itemId, $desc, $uom, $qty, $unit) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['mcs-ge-allocation']);
            }
            $ge = $requestedGe ?? ((Order::max('ge_number') ?? 1000) + 1);
            // Serialize concurrent additions to a pre-allocated GE on PostgreSQL.
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(?)', [(int) $ge]);
            }
            $lines = Order::where('ge_number', $ge)->lockForUpdate()->get();
            $header = $lines->first();
            if ($header && ($lines->contains(fn ($line) => $line->status !== 'Pending')
                || $header->supplier_id != $data['supplier_id']
                || $header->inventory_flag !== $data['inventory_flag']
                || $header->branch_id != Auth::user()->branch_id
                || $header->account_code !== ($data['account_code'] ?? null))) {
                throw ValidationException::withMessages(['ge_number' => 'Add lines only to a Pending GE with the same supplier, flag, branch and account code.']);
            }
            Order::create([
                'ge_number' => $ge,
                'inventory_flag' => $data['inventory_flag'],
                'po_number' => null,
                'supplier_id' => $data['supplier_id'],
                'order_date' => $header ? $header->order_date : now()->toDateString(),
                'user_id' => Auth::user()->user_id,
                'branch_id' => Auth::user()->branch_id ?? 201,
                'account_code' => $data['account_code'] ?? null,
                'item_id' => $itemId,
                'item' => $desc,
                'uom' => $uom,
                'quantity' => $qty,
                'unit_cost' => $unit,
                'total_cost' => round($qty * $unit, 2),
                'status' => 'Pending',
            ]);
            return $ge;
        }, 5);

        return redirect()->route('orders.index')->with('success', "GE #{$ge} created (Pending). Status is updated manually by Purchasing.");
    }

    public function edit(Order $order)
    {
        if ($order->status !== 'Pending' || $order->receipts()->exists()) {
            throw ValidationException::withMessages(['order' => 'Only unreceipted Pending lines can be edited.']);
        }
        return view('orders.create', [
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
