@extends('layouts.app')
@section('title', 'Purchase Receipts')
@section('actions')
@if(auth()->user()->canWritePurchasing())
<a href="{{ route('receipts.create') }}" class="btn btn-primary">Add Receipt</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>Receipt ID</th><th>PO / GE Number</th><th>Supplier</th><th>Item ID</th><th>Ordered Qty</th><th>Received Qty</th><th>Receipt Date</th><th>Received By</th><th>GRN / Invoice</th>@if(auth()->user()->canWritePurchasing())<th>Actions</th>@endif</tr>
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
                    @if(auth()->user()->canWritePurchasing())
                    <td class="actions-cell">
                        <a href="{{ route('receipts.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('receipts.destroy', $r) }}" onsubmit="return confirm('Cancel receipt and restore its stock and cost?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No receipts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
