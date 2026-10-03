<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemBranch;
use App\Support\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $branches = Branch::orderBy('branch_id')->get();
        return view('adjustments.form', compact('items', 'nextId', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'adjustment_number' => 'nullable|integer|between:5001,5999|unique:adjustments,adjustment_number',
            'adjustment_type' => 'required|in:IN,OUT',
            'item_id' => 'required|exists:items,item_id',
            'branch_id' => 'nullable|integer|exists:branches,branch_id',
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
            $adjustment = Adjustment::whereKey($adjustment->getKey())->lockForUpdate()->firstOrFail();
            $ib = ItemBranch::where('item_id', $adjustment->item_id)
                ->where('branch_id', $adjustment->branch_id)->lockForUpdate()->first();
            if (!$ib) {
                throw ValidationException::withMessages(['delete' => 'The Item Branch is missing; cancellation cannot reverse stock.']);
            }
            if ($adjustment->adjustment_type === 'IN' && $ib->current_stock < $adjustment->quantity) {
                throw ValidationException::withMessages(['delete' => 'Cannot cancel: the added stock has already been used.']);
            }
            $ib->current_stock = $adjustment->adjustment_type === 'IN'
                ? $ib->current_stock - $adjustment->quantity
                : $ib->current_stock + $adjustment->quantity;
            $ib->save();
            $adjustment->delete();
        });
        return redirect()->route('adjustments.index')->with('success', 'Adjustment cancelled and stock reversed.');
    }

    public function edit(Adjustment $adjustment)
    {
        return view('adjustments.form', [
            'row' => $adjustment, 'nextId' => $adjustment->adjustment_number,
            'items' => Item::orderBy('item')->get(),
            'branches' => Branch::orderBy('branch_id')->get(),
        ]);
    }

    public function update(Request $request, Adjustment $adjustment)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'branch_id' => 'required|exists:branches,branch_id',
            'adjustment_type' => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
            'purpose' => 'nullable|string|max:500',
        ]);
        DB::transaction(function () use ($adjustment, $data) {
            $adjustment = Adjustment::whereKey($adjustment->getKey())->lockForUpdate()->firstOrFail();
            $rows = ItemBranch::where(function ($query) use ($adjustment, $data) {
                $query->where(fn ($q) => $q->where('item_id', $adjustment->item_id)->where('branch_id', $adjustment->branch_id))
                    ->orWhere(fn ($q) => $q->where('item_id', $data['item_id'])->where('branch_id', $data['branch_id']));
            })->orderBy('item_id')->orderBy('branch_id')->lockForUpdate()->get();
            $old = $rows->first(fn ($r) => $r->item_id == $adjustment->item_id && $r->branch_id == $adjustment->branch_id);
            $new = $rows->first(fn ($r) => $r->item_id == $data['item_id'] && $r->branch_id == $data['branch_id']);
            if (!$old || !$new) {
                throw ValidationException::withMessages(['item_id' => 'Both the original and selected Item Branch must exist.']);
            }
            $old->current_stock -= $adjustment->adjustment_type === 'IN' ? $adjustment->quantity : -$adjustment->quantity;
            $new->current_stock += $data['adjustment_type'] === 'IN' ? $data['quantity'] : -$data['quantity'];
            if ($old->current_stock < 0 || $new->current_stock < 0) {
                throw ValidationException::withMessages(['quantity' => 'The revised adjustment would make stock negative.']);
            }
            $old->save();
            if ($new !== $old) {
                $new->save();
            }
            $data['uom'] = $new->uom;
            $adjustment->update($data);
        });
        return redirect()->route('adjustments.index')->with('success', 'Adjustment updated and stock reconciled.');
    }
}
