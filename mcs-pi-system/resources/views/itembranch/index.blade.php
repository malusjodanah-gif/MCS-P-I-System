@extends('layouts.app')
@section('title', 'Item Branch')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('itembranch.create') }}" class="btn btn-primary">Add Item Branch</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Item ID</th><th>Branch ID</th><th>UOM</th><th>Current Stock</th>
                    <th>Unit Cost</th><th>Location</th><th>Reorder Level</th><th>Reorder Quantity</th>
                    @if(auth()->user()->canWriteInventory())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr class="{{ $r->needsReorder() ? 'low-stock' : '' }}">
                    <td>{{ $r->item_id }} {{ $r->item->item ?? '' }}</td>
                    <td>{{ $r->branch_id }}</td>
                    <td>{{ $r->uom }}</td>
                    <td><strong>{{ $r->current_stock }}</strong></td>
                    <td>{{ number_format($r->unit_cost, 4) }}</td>
                    <td>{{ $r->location }}</td>
                    <td>{{ $r->reorder_level }}{{ $r->needsReorder() ? ' (≤ stock)' : '' }}</td>
                    <td>{{ $r->reorder_quantity }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <a href="{{ route('itembranch.edit', [$r->item_id, $r->branch_id]) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('itembranch.destroy', [$r->item_id, $r->branch_id]) }}" onsubmit="return confirm('Delete this Item Branch record?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No Item Branch records. Add Items with stock or create Item Branch rows.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
