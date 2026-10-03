<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'ge_number' => 'nullable|integer',
            'inventory_flag' => 'required|in:STOCK,NON-STOCK',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'account_code' => 'nullable|string|max:50',
            'item_id' => 'nullable|exists:items,item_id',
            'item' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        $ge = $data['ge_number'] ?? ((Order::max('ge_number') ?? 1000) + 1);
        $desc = $data['item'] ?? null;
        $uom = null;
        $itemId = $data['item_id'] ?? null;

        if ($data['inventory_flag'] === 'STOCK') {
            if (!$itemId) {
                return back()->withErrors(['item_id' => 'Item is required for STOCK orders'])->withInput();
            }
            $item = Item::findOrFail($itemId);
            $desc = $desc ?: $item->item;
            $uom = $item->uom;
        } else {
            $itemId = null;
            if (!$desc) {
                return back()->withErrors(['item' => 'Description required for NON-STOCK'])->withInput();
            }
        }

        $qty = (int) $data['quantity'];
        $unit = (float) $data['unit_cost'];

        Order::create([
            'ge_number' => $ge,
            'inventory_flag' => $data['inventory_flag'],
            'po_number' => $ge,
            'supplier_id' => $data['supplier_id'],
            'order_date' => now()->toDateString(),
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

        return redirect()->route('orders.index')->with('success', "GE #{$ge} created (Pending). Status is updated manually by Purchasing.");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Order::STATUSES),
            'update_whole_ge' => 'nullable|boolean',
        ]);

        if ($request->boolean('update_whole_ge')) {
            Order::where('ge_number', $order->ge_number)->update(['status' => $data['status']]);
        } else {
            $order->update(['status' => $data['status']]);
        }

        return back()->with('success', 'Status updated to ' . $data['status']);
    }

    public function destroy(Order $order)
    {
        if (!in_array($order->status, ['Pending', 'Rejected', 'Cancelled'], true)) {
            return back()->withErrors(['status' => 'Only Pending/Rejected/Cancelled lines can be deleted.']);
        }
        $order->delete();
        return back()->with('success', 'Order line deleted.');
    }
}
