@extends('layouts.app')
@section('title', $row ? 'Edit Asset' : 'Add Asset')
@section('content')
<div class="card" style="max-width:720px">
    <form method="POST" action="{{ $row ? route('assets.update', $row) : route('assets.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>Asset ID (3001–3999)</label>
                <input type="number" name="asset_id" value="{{ old('asset_id', $nextId) }}" min="3001" max="3999">
            </div>
            @endif
            <div class="form-group">
                <label>Asset</label>
                <input type="text" name="asset" value="{{ old('asset', $row->asset ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Serial number</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $row->serial_number ?? '') }}">
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" value="{{ old('brand', $row->brand ?? '') }}">
            </div>
            <div class="form-group">
                <label>Model</label>
                <input type="text" name="model" value="{{ old('model', $row->model ?? '') }}">
            </div>
            <div class="form-group">
                <label>Purchase date</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($row->purchase_date ?? null)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>Unit cost</label>
                <input type="number" name="unit_cost" value="{{ old('unit_cost', $row->unit_cost ?? '') }}" min="0" step="0.01">
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="{{ old('location', $row->location ?? '') }}">
            </div>
            <div class="form-group">
                <label>Branch</label>
                <select name="branch_id" required>
                    @foreach($branches as $b)
                        <option value="{{ $b->branch_id }}" @selected(old('branch_id', $row->branch_id ?? 201)==$b->branch_id)>{{ $b->branch_id }} — {{ $b->branch }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    @foreach(['new','used','under repair','out-of-service'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $row->status ?? 'new')===$s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Item ID (ASSET item)</label>
                <select name="item_id" required>
                    <option value="">— select an item —</option>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" @selected(old('item_id', $row->item_id ?? '')==$i->item_id)>{{ $i->item_id }} — {{ $i->item }}</option>
                    @endforeach
                </select>
                <small>Select an ASSET item that has an Item Branch record for this branch.</small>
            </div>
            <div class="form-group">
                <label>PO number</label>
                <input type="number" name="po_number" value="{{ old('po_number', $row->po_number ?? '') }}">
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('assets.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
