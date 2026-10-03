<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Branch;
use App\Models\Item;
use App\Support\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index()
    {
        $rows = Asset::with(['item', 'branch'])->orderBy('asset_id')->get();
        return view('assets.index', compact('rows'));
    }

    public function create()
    {
        $nextId = IdGenerator::next('assets', 'asset_id', 3001, 3999);
        $items = Item::where('category', 'ASSET')->orderBy('item')->get();
        $branches = Branch::orderBy('branch_id')->get();
        return view('assets.form', ['row' => null, 'nextId' => $nextId, 'items' => $items, 'branches' => $branches]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id' => 'nullable|integer|between:3001,3999|unique:assets,asset_id',
            'asset' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'branch_id' => 'required|exists:branches,branch_id',
            'status' => 'required|in:new,used,under repair,out-of-service',
            'po_number' => 'nullable|integer',
            'item_id' => ['required', Rule::exists('items', 'item_id')->where('category', 'ASSET'),
                Rule::exists('item_branches', 'item_id')->where('branch_id', $request->input('branch_id'))],
        ]);
        IdGenerator::create('assets', 'asset_id', 3001, 3999, $data['asset_id'] ?? null,
            fn ($id) => Asset::create(array_replace($data, ['asset_id' => $id])));
        return redirect()->route('assets.index')->with('success', 'Asset created.');
    }

    public function edit(Asset $asset)
    {
        $items = Item::where('category', 'ASSET')->orderBy('item')->get();
        $branches = Branch::orderBy('branch_id')->get();
        return view('assets.form', ['row' => $asset, 'nextId' => null, 'items' => $items, 'branches' => $branches]);
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'asset' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'branch_id' => 'required|exists:branches,branch_id',
            'status' => 'required|in:new,used,under repair,out-of-service',
            'po_number' => 'nullable|integer',
            'item_id' => ['required', Rule::exists('items', 'item_id')->where('category', 'ASSET'),
                Rule::exists('item_branches', 'item_id')->where('branch_id', $request->input('branch_id'))],
        ]);
        $asset->update($data);
        return redirect()->route('assets.index')->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Asset deleted.');
    }
}
