@extends('layouts.app')
@section('title', 'New GE Order')
@section('content')
@php
    $draftLines = old('lines', []);
    $count = min(100, max(10, (int) ceil(count($draftLines) / 10) * 10));
@endphp
<div class="card" style="max-width:800px">
    <form id="ge-order-form" method="POST" action="{{ route('orders.store') }}">
        @csrf
        <h3>Order header</h3>
        <p class="text-muted mb-1">All lines share this branch, supplier, date, account code and inventory flag.</p>
        <div class="form-grid">
            <div class="form-group"><label for="ge-number">GE Number</label><input id="ge-number" type="number" name="ge_number" min="1" max="2147483647" value="{{ old('ge_number', $nextGe) }}"></div>
            <div class="form-group"><label for="ge-branch">Branch</label><select id="ge-branch" name="branch_id" required>@foreach($branches as $branch)<option value="{{ $branch->branch_id }}" @selected(old('branch_id', auth()->user()->branch_id ?? 201)==$branch->branch_id)>{{ $branch->branch_id }} — {{ $branch->branch }}</option>@endforeach</select></div>
            <div class="form-group"><label for="ge-supplier">Supplier</label><select id="ge-supplier" name="supplier_id" required>@foreach($suppliers as $supplier)<option value="{{ $supplier->supplier_id }}" data-currency="{{ $supplier->currency }}" @selected(old('supplier_id')==$supplier->supplier_id)>{{ $supplier->supplier_id }} — {{ $supplier->supplier }}</option>@endforeach</select></div>
            <div class="form-group"><label for="ge-date">Date</label><input id="ge-date" type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required></div>
            <div class="form-group"><label for="inventory-flag">Inventory Flag</label><select id="inventory-flag" name="inventory_flag"><option value="STOCK" @selected(old('inventory_flag', 'STOCK')==='STOCK')>STOCK</option><option value="NON-STOCK" @selected(old('inventory_flag')==='NON-STOCK')>NON-STOCK</option></select></div>
            <div class="form-group"><label for="ge-account">Account Code</label><input id="ge-account" name="account_code" maxlength="50" value="{{ old('account_code') }}"><small>The school’s budget or expenditure code for reporting.</small></div>
        </div>
        <h3 class="mt-2">Order lines</h3>
        <p class="text-muted">Complete only the lines needed. Blank lines are ignored. Use “Clear line” to remove an entry.</p>
        <div id="ge-lines">
            @for($index = 0; $index < $count; $index++)
                @include('orders.line', ['index' => $index, 'line' => $draftLines[$index] ?? []])
            @endfor
        </div>
        <button id="add-ge-lines" type="button" class="btn btn-secondary">+ Add 10 more lines</button>
        <small id="ge-line-limit" class="text-muted" hidden>Maximum 100 lines per submission.</small>
        <div class="order-total money" role="status" aria-live="polite">Order total: <output id="ge-grand-total">0.00</output></div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary">Save entire GE (Pending)</button><a class="btn btn-secondary" href="{{ route('orders.index') }}">Cancel</a></div>
    </form>
</div>
<template id="ge-line-template">@include('orders.line', ['index' => '__INDEX__', 'line' => []])</template>
<style>.ge-line [hidden] { display: none; }</style>
<script src="{{ asset('js/ge-order.js') }}" defer></script>
@endsection
