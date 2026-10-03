<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemBranch;
use Illuminate\Http\Request;

class ItemBranchController extends Controller
{
    public function index()
    {
        $rows = ItemBranch::with(['item', 'branch'])->orderBy('item_id')->orderBy('branch_id')->get();
        return view('itembranch.index', compact('rows'));
    }

    public function create()
    {
        $items = Item::orderBy('item')->get();
        $branches = Branch::orderBy('branch_id')->get();
        return view('itembranch.form', ['row' => null, 'items' => $items, 'branches' => $branches]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'branch_id' => 'required|exists:branches,branch_id',
            'uom' => 'required|string|max:50',
            'current_stock' => 'required|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
            'reorder_level' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:0',
        ]);

        if (ItemBranch::where('item_id', $data['item_id'])->where('branch_id', $data['branch_id'])->exists()) {
            return back()->withErrors(['item_id' => 'Stock record already exists for this item/branch.'])->withInput();
        }

        ItemBranch::create($data);
        return redirect()->route('itembranch.index')->with('success', 'Stock location created.');
    }

    public function edit($itemId, $branchId)
    {
        $row = ItemBranch::where('item_id', $itemId)->where('branch_id', $branchId)->firstOrFail();
        $items = Item::orderBy('item')->get();
        $branches = Branch::orderBy('branch_id')->get();
        return view('itembranch.form', compact('row', 'items', 'branches'));
    }

    public function update(Request $request, $itemId, $branchId)
    {
        $row = ItemBranch::where('item_id', $itemId)->where('branch_id', $branchId)->firstOrFail();
        // Current stock is system-managed via receipts/issues/adjustments;
        // allow manual unit_cost, location, reorder fields, uom
        $data = $request->validate([
            'uom' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
            'reorder_level' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:0',
        ]);
        $row->update($data);
        return redirect()->route('itembranch.index')->with('success', 'Stock record updated.');
    }

    public function destroy($itemId, $branchId)
    {
        $row = ItemBranch::where('item_id', $itemId)->where('branch_id', $branchId)->firstOrFail();
        $row->delete();
        return redirect()->route('itembranch.index')->with('success', 'Stock record deleted.');
    }
}
