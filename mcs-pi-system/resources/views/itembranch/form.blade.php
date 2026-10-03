@extends('layouts.app')
@section('title', $row ? 'Edit Item Branch' : 'Add Item Branch')
@section('content')
<div class="card" style="max-width:640px">
    <form method="POST" action="{{ $row ? route('itembranch.update', [$row->item_id, $row->branch_id]) : route('itembranch.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>Item ID</label>
                <select name="item_id" required>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" @selected(old('item_id')==$i->item_id)>{{ $i->item_id }} — {{ $i->item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Branch ID</label>
                <select name="branch_id" required>
                    @foreach($branches as $b)
                        <option value="{{ $b->branch_id }}" @selected(old('branch_id', 201)==$b->branch_id)>{{ $b->branch_id }} — {{ $b->branch }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Current Stock</label>
                <input type="number" value="0" disabled>
                <small>Stock is maintained through Receipts, Issues and Adjustments.</small>
            </div>
            @else
            <div class="form-group">
                <label>Item ID</label>
                <input type="text" value="{{ $row->item_id }} — {{ $row->item->item ?? '' }}" disabled>
            </div>
            <div class="form-group">
                <label>Branch ID</label>
                <input type="text" value="{{ $row->branch_id }}" disabled>
            </div>
            <div class="form-group">
                <label>Current Stock</label>
                <input type="text" value="{{ $row->current_stock }}" disabled>
            </div>
            @endif
            <div class="form-group">
                <label>UOM</label>
                <input type="text" value="{{ $row->uom ?? 'From selected item' }}" disabled>
            </div>
            <div class="form-group">
                <label>Unit Cost</label>
                <input type="number" name="unit_cost" value="{{ old('unit_cost', $row->unit_cost ?? 0) }}" min="0" step="0.0001" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="{{ old('location', $row->location ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Reorder Level</label>
                <input type="number" name="reorder_level" value="{{ old('reorder_level', $row->reorder_level ?? 0) }}" min="0" required>
            </div>
            <div class="form-group">
                <label>Reorder Quantity</label>
                <input type="number" name="reorder_quantity" value="{{ old('reorder_quantity', $row->reorder_quantity ?? 0) }}" min="0" required>
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('itembranch.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
