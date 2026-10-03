@extends('layouts.app')
@section('title', 'Add Receipt')
@section('content')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('receipts.store') }}">
        @csrf
        <div class="form-group">
            <label>Order Line (Approved / Ordered)</label>
            <select name="order_line_id" required>
                @foreach($lines as $o)
                    <option value="{{ $o->order_line_id }}">GE {{ $o->ge_number }} · {{ $o->item }} (qty {{ $o->quantity }}) · {{ $o->status }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-grid mt-1">
            <div class="form-group"><label>Qty Received</label><input type="number" name="quantity_received" min="0" value="1" required></div>
            <div class="form-group"><label>Received By</label><input type="text" name="received_by" value="{{ auth()->user()->name }}" required></div>
            <div class="form-group"><label>GRN / Invoice #</label><input type="text" name="grn"></div>
        </div>
        <p class="text-muted mt-1" style="font-size:0.8rem">STOCK receipts update ItemBranch stock and weighted-average unit cost.</p>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save Receipt</button>
            <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
