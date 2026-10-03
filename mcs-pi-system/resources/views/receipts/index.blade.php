@extends('layouts.app')
@section('title', 'Purchase Receipts')
@section('actions')
@if(auth()->user()->canWritePurchasing() || auth()->user()->canWriteInventory())
<a href="{{ route('receipts.create') }}" class="btn btn-primary">Add Receipt</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>Receipt ID</th><th>PO / GE Number</th><th>Supplier</th><th>Item ID</th><th>Ordered Qty</th><th>Received Qty</th><th>Receipt Date</th><th>Received By</th><th>GRN / Invoice</th></tr>
            </thead>
            <tbody>
            @forelse($receipts as $r)
                <tr>
                    <td>{{ $r->receipt_id }}</td>
                    <td>{{ $r->po_number }}</td>
                    <td>{{ $r->supplier->supplier ?? $r->supplier_id }}</td>
                    <td>{{ $r->item_id ?? '—' }}</td>
                    <td>{{ $r->quantity_ordered }}</td>
                    <td>{{ $r->quantity_received }}</td>
                    <td>{{ $r->date_received->format('Y-m-d') }}</td>
                    <td>{{ $r->received_by }}</td>
                    <td>{{ $r->grn ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No receipts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
