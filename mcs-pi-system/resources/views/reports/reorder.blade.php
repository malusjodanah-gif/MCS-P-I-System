@extends('layouts.app')
@section('title', 'Reorder List')
@section('content')
<div class="card">
    <p class="mb-1 text-muted">Current operational records across branches.</p>
    <div class="table-wrap" tabindex="0" role="region" aria-label="Reorder List table">
        <table class="data">
            <caption>Reorder List</caption>
            <thead><tr><th scope="col">Item ID</th><th scope="col">Item</th><th scope="col">Branch ID</th><th scope="col">Branch</th><th scope="col">UOM</th><th scope="col">Current Stock</th><th scope="col">Reorder Level</th><th scope="col">Reorder Quantity</th><th scope="col">Location</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr><td>{{ $r->item_id }}</td><td>{{ $r->item->item ?? '—' }}</td><td>{{ $r->branch_id }}</td><td>{{ $r->branch->branch ?? '—' }}</td><td>{{ $r->uom }}</td><td>{{ $r->current_stock }}</td><td>{{ $r->reorder_level }}</td><td>{{ $r->reorder_quantity }}</td><td>{{ $r->location }}</td></tr>
            @empty
                <tr><td colspan="9" class="text-muted">No records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-1"><a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">← Back to reports</a></p>
</div>
@endsection
