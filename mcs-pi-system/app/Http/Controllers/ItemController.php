<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Supplier;
use App\Models\ItemBranch;
use App\Support\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    public function index()
    {
        $rows = Item::with('supplier')->orderBy('item_id')->get();
        return view('items.index', compact('rows'));
    }

    public function create()
    {
        $nextId = IdGenerator::next('items', 'item_id', 1001, 1999);
        $suppliers = Supplier::orderBy('supplier')->get();
        return view('items.form', ['row' => null, 'nextId' => $nextId, 'suppliers' => $suppliers]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => 'nullable|integer|between:1001,1999|unique:items,item_id',
            'item' => 'required|string|max:255',
            'uom' => 'required|string|max:50',
            'category' => 'required|in:ASSET,CONSUMABLE',
            'sub_category' => ['required', Rule::in($request->input('category') === 'ASSET'
                ? ['Computer', 'ICT Equipment', 'Tools Equipment', 'Fixtures & Furniture']
                : ['Stationery', 'Cleaning', 'General', 'Staff'])],
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'create_stock' => 'nullable|boolean',
            'current_stock' => 'nullable|integer|in:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'required_unless:create_stock,0|string|max:255',
            'reorder_level' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|integer|exists:branches,branch_id',
        ]);

        $created = IdGenerator::create('items', 'item_id', 1001, 1999, $data['item_id'] ?? null, function ($id) use ($data) {
            $item = Item::create([
                'item_id' => $id,
                'item' => $data['item'],
                'uom' => $data['uom'],
                'category' => $data['category'],
                'sub_category' => $data['sub_category'],
                'supplier_id' => $data['supplier_id'] ?? null,
            ]);
            if ($data['create_stock'] ?? true) {
                ItemBranch::create([
                    'item_id' => $id,
                    'branch_id' => $data['branch_id'] ?? 201,
                    'uom' => $data['uom'],
                    'current_stock' => 0,
                    'unit_cost' => $data['unit_cost'] ?? 0,
                    'location' => $data['location'],
                    'reorder_level' => $data['reorder_level'] ?? 0,
                    'reorder_quantity' => $data['reorder_quantity'] ?? 0,
                ]);
            }
            return $item;
        });

        return redirect()->route('items.index')->with('success', "Item #{$created->item_id} created.");
    }

    public function edit(Item $item)
    {
        $suppliers = Supplier::orderBy('supplier')->get();
        return view('items.form', ['row' => $item, 'nextId' => null, 'suppliers' => $suppliers]);
    }

    public function update(Request $request, Item $item)
    {
        $data = $request->validate([
            'item' => 'required|string|max:255',
            'uom' => 'required|string|max:50',
            'category' => 'required|in:ASSET,CONSUMABLE',
            'sub_category' => ['required', Rule::in($request->input('category') === 'ASSET'
                ? ['Computer', 'ICT Equipment', 'Tools Equipment', 'Fixtures & Furniture']
                : ['Stationery', 'Cleaning', 'General', 'Staff'])],
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
        ]);
        DB::transaction(function () use ($item, $data) {
            $item->update($data);
            ItemBranch::where('item_id', $item->item_id)->update(['uom' => $item->uom]);
        });
        return redirect()->route('items.index')->with('success', 'Item updated.');
    }

    public function destroy(Item $item)
    {
        try {
            DB::transaction(function () use ($item) {
                $item = Item::whereKey($item->getKey())->lockForUpdate()->firstOrFail();
                foreach (['orders', 'receipts', 'issues', 'adjustments', 'assets'] as $table) {
                    if (DB::table($table)->where('item_id', $item->item_id)->exists()) {
                        throw ValidationException::withMessages(['delete' => 'Cannot delete an item referenced by operational records.']);
                    }
                }
                if (ItemBranch::where('item_id', $item->item_id)->where('current_stock', '>', 0)->exists()) {
                    throw ValidationException::withMessages(['delete' => 'Cannot delete an item with stock remaining.']);
                }
                ItemBranch::where('item_id', $item->item_id)->delete();
                $item->delete();
            });
            return redirect()->route('items.index')->with('success', 'Item deleted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['delete' => 'Cannot delete: item is referenced by other records.']);
        }
    }
}
