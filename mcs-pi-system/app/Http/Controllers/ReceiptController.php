<?php

namespace App\Http\Controllers;

use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::with(['supplier', 'item', 'order'])
            ->orderByDesc('date_received')
            ->orderByDesc('receipt_id')
            ->get();
        return view('receipts.index', compact('receipts'));
    }

    public function create()
    {
        $lines = Order::with('supplier')
            ->whereIn('status', ['Approved', 'Ordered', 'Backorder', 'Received'])
            ->orderByDesc('ge_number')
            ->get();
        return view('receipts.create', compact('lines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_line_id' => 'required|exists:orders,order_line_id',
            'quantity_received' => 'required|integer|min:1',
            'received_by' => 'required|string|max:100',
            'grn' => 'nullable|string|max:100',
        ]);

        $qtyRecv = (int) $data['quantity_received'];

        DB::transaction(function () use ($data, $qtyRecv) {
            // Serialize receipts of the same line before checking the remainder.
            $order = Order::whereKey($data['order_line_id'])->lockForUpdate()->firstOrFail();
            if (!in_array($order->status, ['Approved', 'Ordered', 'Backorder', 'Received'], true)) {
                throw ValidationException::withMessages(['order_line_id' => 'Order must be Approved/Ordered before receipt.']);
            }
            $received = (int) $order->receipts()->sum('quantity_received');
            $remaining = $order->quantity - $received;
            if ($qtyRecv > $remaining) {
                throw ValidationException::withMessages(['quantity_received' => "Only {$remaining} units remain on this order line."]);
            }
            Receipt::create([
                'po_number' => $order->po_number ?? $order->ge_number,
                'branch_id' => $order->branch_id,
                'item_id' => $order->item_id,
                'supplier_id' => $order->supplier_id,
                'quantity_ordered' => $order->quantity,
                'quantity_received' => $qtyRecv,
                'uom' => $order->uom,
                'received_by' => $data['received_by'],
                'date_received' => now()->toDateString(),
                'grn' => $data['grn'] ?? null,
                'account_code' => $order->account_code,
                'order_line_id' => $order->order_line_id,
            ]);

            // STOCK: update ItemBranch + weighted average unit cost
            if ($order->inventory_flag === 'STOCK' && $order->item_id && $qtyRecv > 0) {
                $ib = ItemBranch::where('item_id', $order->item_id)
                    ->where('branch_id', $order->branch_id)
                    ->lockForUpdate()
                    ->first();
                if (!$ib) {
                    throw ValidationException::withMessages(['order_line_id' => 'Create an Item Branch location for this item and branch before receiving stock.']);
                }
                $currentStock = $ib->current_stock;
                $currentCost = (float) $ib->unit_cost;
                $currentValue = $currentStock * $currentCost;
                $amountReceived = $qtyRecv * (float) $order->unit_cost;
                $newStock = $currentStock + $qtyRecv;
                $newUnitCost = $newStock > 0
                    ? ($amountReceived + $currentValue) / $newStock
                    : $currentCost;

                $ib->current_stock = $newStock;
                $ib->unit_cost = round($newUnitCost, 4);
                $ib->save();
            }

            // GE status remains a manual Purchasing Officer/Administrator action.
        });

        return redirect()->route('receipts.index')->with('success', 'Receipt recorded.');
    }
}
