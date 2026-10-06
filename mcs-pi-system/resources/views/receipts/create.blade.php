@extends('layouts.app')
@section('title', 'Add Receipt')
@section('content')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('receipts.store') }}">
        @csrf
        <div class="form-group">
            <label>Order Line (Approved / Ordered / Received / Backorder)</label>
            <select name="order_line_id" required>
                @foreach($lines as $o)
                    <option value="{{ $o->order_line_id }}">GE {{ $o->ge_number }} · {{ $o->item }} · PO {{ $o->po_number ?? $o->ge_number }} · Branch {{ $o->branch_id }} · Item ID {{ $o->item_id ?? 'NON-STOCK' }} · Remaining {{ $o->quantity - (int) $o->receipts_sum_quantity_received }} {{ $o->uom }} · {{ $o->status }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-grid mt-1">
            <div class="form-group"><label>Quantity Received</label><input type="number" name="quantity_received" min="1" value="{{ old('quantity_received', 1) }}" required></div>
            <div class="form-group"><label>Date Received</label><input type="date" name="date_received" value="{{ old('date_received', now()->toDateString()) }}" required></div>
            <div class="form-group"><label>Received By</label><input type="text" name="received_by" value="{{ auth()->user()->name }}" required></div>
            <div class="form-group"><label>GRN / Invoice #</label><input type="text" name="grn"></div>
        </div>
        @if($lines->isEmpty())<p class="alert alert-info">No order lines have an outstanding quantity available for receipt.</p>@endif
        <p class="text-muted mt-1" style="font-size:0.8rem">STOCK receipts update ItemBranch stock and weighted-average unit cost.</p>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary" @disabled($lines->isEmpty())>Save Receipt</button>
            <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
