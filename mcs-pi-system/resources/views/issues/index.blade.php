@extends('layouts.app')
@section('title', 'Issue Log')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('issues.create') }}" class="btn btn-primary">Add Issue</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Issue Number</th><th>Date</th><th>Branch ID</th><th>Item ID</th>
                    <th>Quantity</th><th>UOM</th><th>Customer ID</th><th>Purpose</th><th>User ID</th>
                    @if(auth()->user()->canWriteInventory())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->issue_number }}</td>
                    <td>{{ $r->issue_date->format('Y-m-d') }}</td>
                    <td>{{ $r->branch_id }}</td>
                    <td>{{ $r->item_id }} {{ $r->item->item ?? '' }}</td>
                    <td>{{ $r->quantity }}</td>
                    <td>{{ $r->uom }}</td>
                    <td>{{ $r->customer_id }} {{ $r->customer->customer ?? '' }}</td>
                    <td>{{ $r->purpose ?? '—' }}</td>
                    <td>{{ $r->user_id }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <a href="{{ route('issues.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('issues.destroy', $r) }}" onsubmit="return confirm('Cancel this Issue and restore Current Stock?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="10" class="text-muted">No Issue records.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
