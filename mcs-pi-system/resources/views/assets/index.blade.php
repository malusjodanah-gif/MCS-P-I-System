@extends('layouts.app')
@section('title', 'Asset Register')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('assets.create') }}" class="btn btn-primary">Add Asset</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Asset ID</th><th>Asset</th><th>Serial Number</th><th>Brand</th><th>Model</th><th>Date</th><th>Branch ID</th><th>Unit Cost</th>
                    <th>Location</th><th>Status</th><th>Item ID</th><th>PO Number</th>
                    @if(auth()->user()->canWriteInventory())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->asset_id }}</td>
                    <td>{{ $r->asset }}</td>
                    <td>{{ $r->serial_number ?? '—' }}</td>
                    <td>{{ $r->brand ?? '—' }}</td><td>{{ $r->model ?? '—' }}</td>
                    <td>{{ $r->purchase_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $r->branch_id }}</td>
                    <td>{{ $r->unit_cost !== null ? number_format($r->unit_cost, 2) : '—' }}</td>
                    <td>{{ $r->location ?? '—' }}</td>
                    <td><span class="badge badge-stock">{{ $r->status }}</span></td>
                    <td>{{ $r->item_id ?? '—' }}</td>
                    <td>{{ $r->po_number ?? '—' }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <a href="{{ route('assets.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('assets.destroy', $r) }}" onsubmit="return confirm('Delete this asset?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->canWriteInventory() ? 13 : 12 }}" class="text-muted">No assets registered.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

