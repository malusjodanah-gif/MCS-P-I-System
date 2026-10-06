@extends('layouts.app')
@section('title', 'Suppliers')
@section('actions')
@if(auth()->user()->canWritePurchasing())
<a href="{{ route('suppliers.create') }}" class="btn btn-primary">Add Supplier</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Supplier ID</th><th>Supplier</th><th>Contact</th><th>Payment Term</th><th>Currency</th><th>Address</th>
                    @if(auth()->user()->canWritePurchasing())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->supplier_id }}</td>
                    <td>{{ $r->supplier }}</td>
                    <td>{{ $r->contact ?? '—' }}</td>
                    <td>{{ $r->payment_term }}</td>
                    <td>{{ $r->currency }}</td>
                    <td>{{ $r->address ?? '—' }}</td>
                    @if(auth()->user()->canWritePurchasing())
                    <td class="actions-cell">
                        <a href="{{ route('suppliers.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('suppliers.destroy', $r) }}" onsubmit="return confirm('Delete this supplier?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No suppliers.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
