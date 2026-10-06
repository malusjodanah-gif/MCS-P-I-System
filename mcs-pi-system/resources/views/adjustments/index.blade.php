@extends('layouts.app')
@section('title', 'Stock Adjustments')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('adjustments.create') }}" class="btn btn-primary">Add Adjustment</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Adjustment Number</th><th>Adjustment Type</th><th>Date</th><th>Branch ID</th><th>Item ID</th><th>Item</th><th>Quantity</th><th>UOM</th><th>Purpose</th><th>User ID</th>
                    @if(auth()->user()->canWriteInventory())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->adjustment_number }}</td>
                    <td><span class="badge badge-{{ strtolower($r->adjustment_type) }}">{{ $r->adjustment_type }}</span></td>
                    <td>{{ $r->adjustment_date->format('Y-m-d') }}</td>
                    <td>{{ $r->branch_id }}</td>
                    <td>{{ $r->item_id }}</td>
                    <td>{{ $r->item->item ?? $r->item_id }}</td>
                    <td>{{ $r->quantity }}</td>
                    <td>{{ $r->uom }}</td>
                    <td>{{ $r->purpose ?? '—' }}</td>
                    <td>{{ $r->user_id }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <a href="{{ route('adjustments.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('adjustments.destroy', $r) }}" onsubmit="return confirm('Cancel this adjustment and reverse stock?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="11" class="text-muted">No adjustments.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
