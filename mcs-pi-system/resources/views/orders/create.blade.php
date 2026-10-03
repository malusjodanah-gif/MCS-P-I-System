@extends('layouts.app')
@section('title', 'New GE Order')
@section('content')
<div class="card" style="max-width:720px">
    <form method="POST" action="{{ route('orders.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>GE Number</label>
                <input type="number" name="ge_number" value="{{ old('ge_number', $nextGe) }}">
            </div>
            <div class="form-group">
                <label>Inventory Flag</label>
                <select name="inventory_flag">
                    <option value="STOCK" @selected(old('inventory_flag')==='STOCK')>STOCK</option>
                    <option value="NON-STOCK" @selected(old('inventory_flag')==='NON-STOCK')>NON-STOCK</option>
                </select>
            </div>
            <div class="form-group">
                <label>Supplier</label>
                <select name="supplier_id" required>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->supplier_id }}" @selected(old('supplier_id')==$s->supplier_id)>{{ $s->supplier_id }} – {{ $s->supplier }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Account Code</label>
                <input type="text" name="account_code" value="{{ old('account_code') }}">
            </div>
            <div class="form-group">
                <label>Item (STOCK)</label>
                <select name="item_id">
                    <option value="">— select for STOCK —</option>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" @selected(old('item_id')==$i->item_id)>{{ $i->item_id }} – {{ $i->item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Item (description / particulars)</label>
                <input type="text" name="item" value="{{ old('item') }}">
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
            </div>
            <div class="form-group">
                <label>Unit Cost (PGK)</label>
                <input type="number" name="unit_cost" min="0" step="0.01" value="{{ old('unit_cost', 0) }}" required>
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Create GE (Pending)</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
