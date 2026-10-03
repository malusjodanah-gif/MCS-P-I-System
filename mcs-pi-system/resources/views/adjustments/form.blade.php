@extends('layouts.app')
@section('title', 'Stock adjustment')
@section('content')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('adjustments.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Adjustment number (5001–5999)</label>
                <input type="number" name="adjustment_number" value="{{ old('adjustment_number', $nextId) }}" min="5001" max="5999">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="adjustment_type">
                    <option value="IN">IN — increase stock</option>
                    <option value="OUT">OUT — decrease stock</option>
                </select>
            </div>
            <div class="form-group">
                <label>Item</label>
                <select name="item_id" required>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}">{{ $i->item_id }} — {{ $i->item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required>
            </div>
            <div class="form-group full">
                <label>Purpose</label>
                <input type="text" name="purpose" value="{{ old('purpose') }}">
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('adjustments.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
