@extends('layouts.app')
@section('title', $row ? 'Edit Item' : 'Add Item')
@section('content')
<div class="card">
    <form method="POST" action="{{ $row ? route('items.update', $row) : route('items.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        @unless($row)
        <p class="text-muted">Create the item record here. Add branch-specific stock details separately in <a href="{{ route('itembranch.create') }}">Item Branch</a>.</p>
        @endunless
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>Item ID (1001–1999)</label>
                <input type="number" name="item_id" value="{{ old('item_id', $nextId) }}" min="1001" max="1999">
            </div>
            @endif
            <div class="form-group">
                <label>Item</label>
                <input type="text" name="item" value="{{ old('item', $row->item ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>UOM</label>
                <input type="text" name="uom" value="{{ old('uom', $row->uom ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="CONSUMABLE" @selected(old('category', $row->category ?? 'CONSUMABLE')==='CONSUMABLE')>CONSUMABLE</option>
                    <option value="ASSET" @selected(old('category', $row->category ?? '')==='ASSET')>ASSET</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sub-Category</label>
                <select name="sub_category">
                    @foreach(['Stationery','Cleaning','General','Staff','Computer','ICT Equipment','Tools Equipment','Fixtures & Furniture'] as $s)
                        <option value="{{ $s }}" @selected(old('sub_category', $row->sub_category ?? '')===$s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Supplier ID</label>
                <select name="supplier_id">
                    <option value="">— optional —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->supplier_id }}" @selected(old('supplier_id', $row->supplier_id ?? '')==$s->supplier_id)>{{ $s->supplier_id }} — {{ $s->supplier }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('items.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
