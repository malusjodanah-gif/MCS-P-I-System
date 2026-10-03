@extends('layouts.app')
@php $row = $row ?? null; @endphp
@section('title', $row ? 'Edit GE Line' : 'New GE Order')
@section('content')
<div class="card" style="max-width:720px">
    <form method="POST" action="{{ $row ? route('orders.update', $row) : route('orders.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-group">
                <label>GE Number</label>
                <input type="number" name="ge_number" @disabled($row) value="{{ old('ge_number', $nextGe) }}">
            </div>
            <div class="form-group">
                <label>Inventory Flag</label>
                <select name="inventory_flag" @disabled($row)>
                    <option value="STOCK" @selected(old('inventory_flag', $row->inventory_flag ?? 'STOCK')==='STOCK')>STOCK</option>
                    <option value="NON-STOCK" @selected(old('inventory_flag', $row->inventory_flag ?? 'STOCK')==='NON-STOCK')>NON-STOCK</option>
                </select>
            </div>
            <div class="form-group">
                <label>Supplier</label>
                <select name="supplier_id" @disabled($row) required>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->supplier_id }}" @selected(old('supplier_id', $row->supplier_id ?? null)==$s->supplier_id)>{{ $s->supplier_id }} – {{ $s->supplier }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Account Code</label>
                <input type="text" name="account_code" @disabled($row) value="{{ old('account_code', $row->account_code ?? '') }}">
            </div>
            <div class="form-group">
                <label>Item (STOCK)</label>
                <select name="item_id">
                    <option value="">— select for STOCK —</option>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" @selected(old('item_id', $row->item_id ?? null)==$i->item_id)>{{ $i->item_id }} – {{ $i->item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Item (description / particulars)</label>
                <input type="text" name="item" value="{{ old('item', $row->item ?? '') }}">
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', $row->quantity ?? 1) }}" required>
            </div>
            <div class="form-group">
                <label>Unit Cost (PGK)</label>
                <input type="number" name="unit_cost" min="0" step="0.01" value="{{ old('unit_cost', $row->unit_cost ?? 0) }}" required>
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">{{ $row ? 'Save GE Line' : 'Create GE (Pending)' }}</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
