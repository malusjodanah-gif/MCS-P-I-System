@extends('layouts.app')
@section('title', $row ? 'Edit stock location' : 'Add stock location')
@section('content')
<div class="card" style="max-width:640px">
    <form method="POST" action="{{ $row ? route('stock.update', [$row->item_id, $row->branch_id]) : route('stock.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>Item</label>
                <select name="item_id" required>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" @selected(old('item_id')==$i->item_id)>{{ $i->item_id }} — {{ $i->item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Branch</label>
                <select name="branch_id" required>
                    @foreach($branches as $b)
                        <option value="{{ $b->branch_id }}" @selected(old('branch_id', 201)==$b->branch_id)>{{ $b->branch_id }} — {{ $b->branch }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Initial stock</label>
                <input type="number" name="current_stock" value="{{ old('current_stock', 0) }}" min="0" required>
            </div>
            @else
            <div class="form-group">
                <label>Item</label>
                <input type="text" value="{{ $row->item_id }} — {{ $row->item->item ?? '' }}" disabled>
            </div>
            <div class="form-group">
                <label>Branch</label>
                <input type="text" value="{{ $row->branch_id }}" disabled>
            </div>
            <div class="form-group">
                <label>Current stock (system-managed)</label>
                <input type="text" value="{{ $row->current_stock }}" disabled>
            </div>
            @endif
            <div class="form-group">
                <label>UOM</label>
                <input type="text" name="uom" value="{{ old('uom', $row->uom ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Unit cost</label>
                <input type="number" name="unit_cost" value="{{ old('unit_cost', $row->unit_cost ?? 0) }}" min="0" step="0.0001" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="{{ old('location', $row->location ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Reorder level</label>
                <input type="number" name="reorder_level" value="{{ old('reorder_level', $row->reorder_level ?? 0) }}" min="0" required>
            </div>
            <div class="form-group">
                <label>Reorder quantity</label>
                <input type="number" name="reorder_quantity" value="{{ old('reorder_quantity', $row->reorder_quantity ?? 0) }}" min="0" required>
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('stock.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
