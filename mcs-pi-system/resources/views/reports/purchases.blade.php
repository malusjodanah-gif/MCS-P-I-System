@extends('layouts.app')
@section('title', 'Purchase Summary')
@section('content')
<div class="card">
    <p class="mb-1 text-muted">Rows are grouped by GE header and line status. Amounts use the recorded purchasing currency; totals across currencies are not combined.</p>
    <div class="table-wrap" tabindex="0" role="region" aria-label="Purchase Summary table">
        <table class="data">
            <caption>Purchase Summary</caption>
            <thead><tr><th scope="col">GE Number</th><th scope="col">PO Number</th><th scope="col">Supplier ID</th><th scope="col">Supplier</th><th scope="col">Currency</th><th scope="col">Branch ID</th><th scope="col">Inventory Flag</th><th scope="col">Date</th><th scope="col">Account Code</th><th scope="col">Status</th><th scope="col">Line Count</th><th scope="col">Total Cost</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr><td>{{ $r->ge_number }}</td><td>{{ $r->po_number ?? '—' }}</td><td>{{ $r->supplier_id }}</td><td>{{ $r->supplier->supplier ?? '—' }}</td><td>{{ $r->supplier->currency ?? '—' }}</td><td>{{ $r->branch_id }}</td><td>{{ $r->inventory_flag }}</td><td>{{ $r->order_date->format('Y-m-d') }}</td><td>{{ $r->account_code ?? '—' }}</td><td>{{ $r->status }}</td><td>{{ $r->line_count }}</td><td>{{ number_format($r->order_total, 2) }}</td></tr>
            @empty
                <tr><td colspan="12" class="text-muted">No records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-1"><a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">← Back to reports</a></p>
</div>
@endsection
