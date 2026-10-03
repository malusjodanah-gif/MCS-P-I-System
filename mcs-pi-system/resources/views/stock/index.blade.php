@extends('layouts.app')
@section('title', 'Stock Levels')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('stock.create') }}" class="btn btn-primary">Add Stock Location</a>
@endif
@endsection
@section('content')
<div class="card">
    <p class="text-muted mb-1">Track inventory by item and branch, including stock levels, reorder points, and warehouse locations.</p>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Item ID</th><th>Item</th><th>Category</th><th>Branch</th><th>Stock</th>
                    <th>Unit cost</th><th>Value</th><th>Reorder</th><th>Reorder qty</th><th>Location</th>
                    @if(auth()->user()->canWriteInventory())<th></th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr class="{{ $r->needsReorder() ? 'low-stock' : '' }}">
                    <td>{{ $r->item_id }}</td>
                    <td>{{ $r->item->item ?? '—' }}</td>
                    <td>{{ $r->item->category ?? '—' }}</td>
                    <td>{{ $r->branch_id }} {{ $r->branch->branch ?? '' }}</td>
                    <td><strong>{{ $r->current_stock }}</strong> {{ $r->uom }}</td>
                    <td>{{ number_format($r->unit_cost, 2) }}</td>
                    <td>{{ number_format($r->current_stock * $r->unit_cost, 2) }}</td>
                    <td>{{ $r->reorder_level }}{{ $r->needsReorder() ? ' (low)' : '' }}</td>
                    <td>{{ $r->reorder_quantity }}</td>
                    <td>{{ $r->location }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <a href="{{ route('stock.edit', [$r->item_id, $r->branch_id]) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('stock.destroy', [$r->item_id, $r->branch_id]) }}" onsubmit="return confirm('Delete this stock record?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="11" class="text-muted">No stock records. Add items with initial stock or create stock locations.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
