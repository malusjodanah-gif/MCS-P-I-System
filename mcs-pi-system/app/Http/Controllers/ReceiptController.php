<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Issue;
use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\Receipt;
use Illuminate\Http\Request;
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
        $lines = Order::with('supplier')->withSum('receipts', 'quantity_received')
            ->whereIn('status', ['Approved', 'Ordered', 'Backorder', 'Received'])
            ->orderByDesc('ge_number')
            ->get()->filter(fn ($line) => (int) $line->receipts_sum_quantity_received < $line->quantity);

        return view('receipts.create', compact('lines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_line_id' => 'required|exists:orders,order_line_id',
            'quantity_received' => 'required|integer|min:1',
            'received_by' => 'required|string|max:100',
            'date_received' => 'nullable|date',
            'grn' => 'nullable|string|max:100',
        ]);

        $qtyRecv = (int) $data['quantity_received'];

        DB::transaction(function () use ($data, $qtyRecv) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['mcs-po-assignment']);
            }
            // Serialize receipts of the same line before checking the remainder.
            $order = Order::whereKey($data['order_line_id'])->lockForUpdate()->firstOrFail();
            if (! in_array($order->status, ['Approved', 'Ordered', 'Backorder', 'Received'], true)) {
                throw ValidationException::withMessages(['order_line_id' => 'Order must be Approved/Ordered before receipt.']);
            }
            $po = $order->po_number ?? $order->ge_number;
            if (Order::where('po_number', $po)->where('supplier_id', '!=', $order->supplier_id)->exists()
                || Receipt::where('po_number', $po)->where('supplier_id', '!=', $order->supplier_id)->exists()) {
                throw ValidationException::withMessages(['order_line_id' => 'This PO Number belongs to another supplier.']);
            }
            $received = (int) $order->receipts()->sum('quantity_received');
            $remaining = $order->quantity - $received;
            if ($qtyRecv > $remaining) {
                throw ValidationException::withMessages(['quantity_received' => "Only {$remaining} units remain on this order line."]);
            }
            if ($order->inventory_flag === 'STOCK' && ! $order->item_id) {
                throw ValidationException::withMessages(['order_line_id' => 'This STOCK order has no item link. Repair the legacy order before receiving.']);
            }
            $receipt = Receipt::create([
                'po_number' => $order->po_number ?? $order->ge_number,
                'branch_id' => $order->branch_id,
                'item_id' => $order->item_id,
                'supplier_id' => $order->supplier_id,
                'quantity_ordered' => $order->quantity,
                'quantity_received' => $qtyRecv,
                'uom' => $order->uom,
                'received_by' => $data['received_by'],
                'date_received' => $data['date_received'] ?? now()->toDateString(),
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
                if (! $ib) {
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
                $receipt->forceFill([
                    'stock_before' => $currentStock,
                    'cost_before' => $currentCost,
                    'purchase_unit_cost' => $order->unit_cost,
                    'stock_after' => $newStock,
                    'cost_after' => round($newUnitCost, 4),
                ])->save();
            }

            // GE status remains a manual Purchasing Officer/Administrator action.
        }, 5);

        return redirect()->route('receipts.index')->with('success', 'Receipt recorded.');
    }

    public function edit(Receipt $receipt)
    {
        return view('receipts/edit', ['row' => $receipt]);
    }

    public function update(Request $request, Receipt $receipt)
    {
        $data = $request->validate([
            'quantity_received' => 'required|integer|min:1',
            'received_by' => 'required|string|max:100',
            'date_received' => 'required|date',
            'grn' => 'nullable|string|max:100',
        ]);
        DB::transaction(function () use ($receipt, $data) {
            // Always lock order before receipt, matching store/status lock order.
            $order = Order::whereKey($receipt->order_line_id)->lockForUpdate()->first();
            $receipt = Receipt::whereKey($receipt->getKey())->lockForUpdate()->firstOrFail();
            if ($data['quantity_received'] != $receipt->quantity_received) {
                $this->assertEditableQuantity($order);
                $otherReceived = $order->receipts()->where('receipt_id', '!=', $receipt->getKey())->sum('quantity_received');
                if ($otherReceived + $data['quantity_received'] > $order->quantity) {
                    throw ValidationException::withMessages(['quantity_received' => 'The revised quantity exceeds the quantity ordered.']);
                }
                if ($order->inventory_flag === 'STOCK') {
                    $ib = $this->reversibleStock($receipt);
                    $newStock = $receipt->stock_before + $data['quantity_received'];
                    $ib->current_stock = $newStock;
                    $ib->unit_cost = round(($receipt->stock_before * (float) $receipt->cost_before
                        + $data['quantity_received'] * (float) $receipt->purchase_unit_cost) / $newStock, 4);
                    $ib->save();
                    $receipt->stock_after = $ib->current_stock;
                    $receipt->cost_after = $ib->unit_cost;
                }
            }
            $receipt->fill($data)->save();
        }, 5);

        return redirect()->route('receipts.index')->with('success', 'Receipt updated. GE status remains manual.');
    }

    public function destroy(Receipt $receipt)
    {
        DB::transaction(function () use ($receipt) {
            $order = Order::whereKey($receipt->order_line_id)->lockForUpdate()->first();
            $receipt = Receipt::whereKey($receipt->getKey())->lockForUpdate()->firstOrFail();
            $this->assertEditableQuantity($order);
            if ($order->inventory_flag === 'STOCK') {
                $ib = $this->reversibleStock($receipt);
                $ib->current_stock = $receipt->stock_before;
                $ib->unit_cost = $receipt->cost_before;
                $ib->save();
            }
            $receipt->delete();
        }, 5);

        return redirect()->route('receipts.index')->with('success', 'Receipt cancelled. Stock and cost restored; GE status remains manual.');
    }

    private function assertEditableQuantity(?Order $order): void
    {
        if (! $order || in_array($order->status, ['Complete', 'Cancelled', 'Rejected'], true)) {
            throw ValidationException::withMessages(['quantity_received' => 'Quantity changes require an active order. For Complete orders, manually set Received first.']);
        }
    }

    private function reversibleStock(Receipt $receipt): ItemBranch
    {
        $ib = ItemBranch::where('item_id', $receipt->item_id)->where('branch_id', $receipt->branch_id)
            ->lockForUpdate()->first();
        $laterReceiptExists = Receipt::where('item_id', $receipt->item_id)
            ->where('branch_id', $receipt->branch_id)
            ->where('receipt_id', '>', $receipt->getKey())
            ->exists();
        $laterIssueExists = Issue::where('item_id', $receipt->item_id)
            ->where('branch_id', $receipt->branch_id)
            ->where('created_at', '>=', $receipt->created_at)
            ->exists();
        $laterAdjustmentExists = Adjustment::where('item_id', $receipt->item_id)
            ->where('branch_id', $receipt->branch_id)
            ->where('created_at', '>=', $receipt->created_at)
            ->exists();
        if (! $ib || $receipt->stock_before === null || $receipt->cost_before === null
            || $receipt->purchase_unit_cost === null || $receipt->stock_after === null
            || $receipt->cost_after === null || $laterReceiptExists || $laterIssueExists || $laterAdjustmentExists) {
            throw ValidationException::withMessages(['quantity_received' => 'Stock or cost has changed since this receipt, or it is a legacy receipt without a valuation snapshot. Quantity cannot be changed or cancelled safely.']);
        }
        $expectedStock = $receipt->stock_before + $receipt->quantity_received;
        $expectedCost = $expectedStock > 0
            ? round(($receipt->stock_before * (float) $receipt->cost_before
                + $receipt->quantity_received * (float) $receipt->purchase_unit_cost) / $expectedStock, 4)
            : (float) $receipt->cost_before;
        if ($ib->current_stock !== (int) $receipt->stock_after
            || abs((float) $ib->unit_cost - (float) $receipt->cost_after) >= 0.00005
            || (int) $receipt->stock_after !== $expectedStock
            || abs((float) $receipt->cost_after - $expectedCost) >= 0.00005) {
            throw ValidationException::withMessages(['quantity_received' => 'Stock or cost has changed since this receipt, or it is a legacy receipt without a valuation snapshot. Quantity cannot be changed or cancelled safely.']);
        }

        return $ib;
    }
}
