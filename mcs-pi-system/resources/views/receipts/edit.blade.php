@extends('layouts.app')
@section('title', 'Edit Receipt')
@section('content')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('receipts.update', $row) }}">
        @csrf @method('PUT')
        <p>PO / GE {{ $row->po_number }} · Receipt {{ $row->receipt_id }} · Ordered {{ $row->quantity_ordered }}</p>
        <div class="form-grid">
            <div class="form-group"><label>Quantity Received</label><input type="number" name="quantity_received" min="1" value="{{ old('quantity_received', $row->quantity_received) }}" required></div>
            <div class="form-group"><label>Received By</label><input type="text" name="received_by" value="{{ old('received_by', $row->received_by) }}" required></div>
            <div class="form-group"><label>Date Received</label><input type="date" name="date_received" value="{{ old('date_received', $row->date_received->format('Y-m-d')) }}" required></div>
            <div class="form-group"><label>GRN / Invoice</label><input type="text" name="grn" value="{{ old('grn', $row->grn) }}"></div>
        </div>
        <p class="text-muted">Stock quantity corrections require unchanged inventory since receipt. GE status remains manual.</p>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save Receipt</button>
            <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
