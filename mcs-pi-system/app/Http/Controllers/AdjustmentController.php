<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Item;
use App\Models\ItemBranch;
use App\Support\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdjustmentController extends Controller
{
    public function index()
    {
        $rows = Adjustment::with(['item', 'user'])->orderByDesc('adjustment_date')->orderByDesc('adjustment_number')->get();
        return view('adjustments.index', compact('rows'));
    }

    public function create()
    {
        $items = Item::orderBy('item')->get();
        $nextId = IdGenerator::next('adjustments', 'adjustment_number', 5001, 5999);
        return view('adjustments.form', compact('items', 'nextId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'adjustment_number' => 'nullable|integer|between:5001,5999|unique:adjustments,adjustment_number',
            'adjustment_type' => 'required|in:IN,OUT',
            'item_id' => 'required|exists:items,item_id',
            'branch_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
            'purpose' => 'nullable|string|max:500',
        ]);

        $branchId = $data['branch_id'] ?? 201;
        $qty = (int) $data['quantity'];
        $type = $data['adjustment_type'];

        try {
        DB::transaction(function () use ($data, $branchId, $qty, $type) {
            $ib = ItemBranch::where('item_id', $data['item_id'])->where('branch_id', $branchId)->lockForUpdate()->first();
            if (!$ib) {
                throw new \RuntimeException('Item is not stocked at this branch.');
            }
            if ($type === 'OUT' && $ib->current_stock < $qty) {
                throw new \RuntimeException("Insufficient stock for OUT. Available: {$ib->current_stock}");
            }

            $num = $data['adjustment_number'] ?? IdGenerator::next('adjustments', 'adjustment_number', 5001, 5999);
            Adjustment::create([
                'adjustment_number' => $num,
                'adjustment_type' => $type,
                'branch_id' => $branchId,
                'item_id' => $data['item_id'],
                'quantity' => $qty,
                'uom' => $ib->uom,
                'adjustment_date' => now()->toDateString(),
                'purpose' => $data['purpose'] ?? null,
                'user_id' => Auth::user()->user_id,
            ]);

            $ib->current_stock = $type === 'IN'
                ? $ib->current_stock + $qty
                : $ib->current_stock - $qty;
            $ib->save();
        });

        return redirect()->route('adjustments.index')->with('success', 'Adjustment recorded.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Adjustment $adjustment)
    {
        DB::transaction(function () use ($adjustment) {
            $ib = ItemBranch::where('item_id', $adjustment->item_id)
                ->where('branch_id', $adjustment->branch_id)->lockForUpdate()->first();
            if ($ib) {
                // reverse the adjustment
                $ib->current_stock = $adjustment->adjustment_type === 'IN'
                    ? max(0, $ib->current_stock - $adjustment->quantity)
                    : $ib->current_stock + $adjustment->quantity;
                $ib->save();
            }
            $adjustment->delete();
        });
        return redirect()->route('adjustments.index')->with('success', 'Adjustment cancelled and stock reversed.');
    }
}
