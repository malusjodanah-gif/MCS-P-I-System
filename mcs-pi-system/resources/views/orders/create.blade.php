@extends('layouts.app')
@php
    $row = $row ?? null;
    $flag = $row ? $row->inventory_flag : old('inventory_flag', 'STOCK');
    $isStock = $flag === 'STOCK';
@endphp
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
                <select id="inventory-flag" name="inventory_flag" @disabled($row)>
                    <option value="STOCK" @selected($flag==='STOCK')>STOCK</option>
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
            <div class="form-group" id="stock-item-group" @if(!$isStock) hidden @endif>
                <label for="stock-item">Item ID (STOCK)</label>
                <select id="stock-item" name="item_id" @required($isStock) @disabled(!$isStock)>
                    <option value="">— select for STOCK —</option>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" data-description="{{ $i->item }}" data-uom="{{ $i->uom }}" @selected(old('item_id', $row->item_id ?? null)==$i->item_id)>{{ $i->item_id }} – {{ $i->item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="line-description">Item (description / particulars)</label>
                <input id="line-description" type="text" name="item" value="{{ old('item', $row->item ?? '') }}" @readonly($isStock) @required(!$isStock)>
                <small id="item-help">{{ $isStock ? 'Description and UOM come from the selected stock item.' : 'Enter the service or one-off purchase description. No stock item is linked.' }}</small>
            </div>
            <div class="form-group" id="stock-uom-group" @if(!$isStock) hidden @endif>
                <label for="stock-uom">UOM</label>
                <input id="stock-uom" type="text" value="{{ $row->uom ?? '' }}" disabled>
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
<script>
(() => {
    const flag = document.getElementById('inventory-flag');
    const item = document.getElementById('stock-item');
    const description = document.getElementById('line-description');
    const uom = document.getElementById('stock-uom');
    let serviceDescription = flag.value === 'NON-STOCK' ? description.value : '';
    let previousStock = flag.value === 'STOCK';
    function sync() {
        const stock = flag.value === 'STOCK';
        if (!previousStock) serviceDescription = description.value;
        document.getElementById('stock-item-group').hidden = !stock;
        document.getElementById('stock-uom-group').hidden = !stock;
        item.disabled = !stock;
        item.required = stock;
        description.readOnly = stock;
        description.required = !stock;
        const selected = item.selectedOptions[0];
        description.value = stock ? (selected?.dataset.description || '') : serviceDescription;
        uom.value = stock ? (selected?.dataset.uom || '') : '';
        document.getElementById('item-help').textContent = stock
            ? 'Description and UOM come from the selected stock item.'
            : 'Enter the service or one-off purchase description. No stock item is linked.';
        previousStock = stock;
    }
    flag.addEventListener('change', sync);
    item.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
