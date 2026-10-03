@extends('layouts.app')
@section('title', 'Stock adjustment')
@php $row = $row ?? null; @endphp
@section('content')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ $row ? route('adjustments.update', $row) : route('adjustments.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-group">
                <label>Adjustment number (5001–5999)</label>
                <input type="number" name="adjustment_number" @disabled($row) value="{{ old('adjustment_number', $nextId) }}" min="5001" max="5999">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="adjustment_type">
                    <option value="IN" @selected(old('adjustment_type', $row->adjustment_type ?? 'IN')==='IN')>IN — increase stock</option>
                    <option value="OUT" @selected(old('adjustment_type', $row->adjustment_type ?? 'IN')==='OUT')>OUT — decrease stock</option>
                </select>
            </div>
            <div class="form-group">
                <label>Item</label>
                <select name="item_id" required>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" @selected(old('item_id', $row->item_id ?? null)==$i->item_id)>{{ $i->item_id }} — {{ $i->item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="{{ old('quantity', $row->quantity ?? 1) }}" min="1" required>
            </div>
            <div class="form-group full">
                <label>Purpose</label>
                <input type="text" name="purpose" value="{{ old('purpose', $row->purpose ?? '') }}">
            </div>
            <div class="form-group">
                <label>Branch</label>
                <select name="branch_id" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->branch_id }}" @selected(old('branch_id', $row->branch_id ?? 201)==$branch->branch_id)>{{ $branch->branch_id }} — {{ $branch->branch }}</option>
                    @endforeach
                </select>
                <small>Stock is checked at the selected branch when saved.</small>
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('adjustments.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
