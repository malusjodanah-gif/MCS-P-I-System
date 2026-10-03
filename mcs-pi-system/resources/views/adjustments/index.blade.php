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
                    <th>Adjustment Number</th><th>Type</th><th>Adjustment Date</th><th>Item</th><th>Quantity</th><th>Purpose</th><th>Created By</th>
                    @if(auth()->user()->canWriteInventory())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->adjustment_number }}</td>
                    <td><span class="badge badge-{{ strtolower($r->adjustment_type) }}">{{ $r->adjustment_type }}</span></td>
                    <td>{{ $r->adjustment_date->format('Y-m-d') }}</td>
                    <td>{{ $r->item->item ?? $r->item_id }} <span class="text-muted">(#{{ $r->item_id }})</span></td>
                    <td>{{ $r->quantity }} {{ $r->uom }}</td>
                    <td>{{ $r->purpose ?? '—' }}</td>
                    <td>{{ $r->user->name ?? $r->user_id }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <form method="POST" action="{{ route('adjustments.destroy', $r) }}" onsubmit="return confirm('Cancel this adjustment and reverse stock?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">No adjustments.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
