@extends('layouts.app')
@section('title', 'Customers')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('customers.create') }}" class="btn btn-primary">Add Customer</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Customer ID</th><th>Customer Name</th><th>Customer Type</th><th>Email</th>
                    @if(auth()->user()->canWriteInventory())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->customer_id }}</td>
                    <td>{{ $r->customer }}</td>
                    <td>{{ $r->customer_type }}</td>
                    <td>{{ $r->email ?? '—' }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <a href="{{ route('customers.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('customers.destroy', $r) }}" onsubmit="return confirm('Delete this customer?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No customers.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
