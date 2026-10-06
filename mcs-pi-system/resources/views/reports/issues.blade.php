@extends('layouts.app')
@section('title', 'Issue History')
@section('content')
<div class="card">
    <p class="mb-1 text-muted">Current operational records across branches.</p>
    <div class="table-wrap" tabindex="0" role="region" aria-label="Issue History table">
        <table class="data">
            <caption>Issue History</caption>
            <thead><tr><th scope="col">Issue Number</th><th scope="col">Date</th><th scope="col">Branch ID</th><th scope="col">Item ID</th><th scope="col">Item</th><th scope="col">Quantity</th><th scope="col">UOM</th><th scope="col">Customer ID</th><th scope="col">Customer</th><th scope="col">Purpose</th><th scope="col">User ID</th><th scope="col">User</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr><td>{{ $r->issue_number }}</td><td>{{ $r->issue_date->format('Y-m-d') }}</td><td>{{ $r->branch_id }}</td><td>{{ $r->item_id }}</td><td>{{ $r->item->item ?? '—' }}</td><td>{{ $r->quantity }}</td><td>{{ $r->uom }}</td><td>{{ $r->customer_id }}</td><td>{{ $r->customer->customer ?? '—' }}</td><td>{{ $r->purpose }}</td><td>{{ $r->user_id }}</td><td>{{ $r->user->name ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="12" class="text-muted">No records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-1"><a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">← Back to reports</a></p>
</div>
@endsection
