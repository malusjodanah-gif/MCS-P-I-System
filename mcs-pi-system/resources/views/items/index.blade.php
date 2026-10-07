@extends('layouts.app')
@section('title', 'Items')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('items.create') }}" class="btn btn-primary">Add Item</a>
@endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Item ID</th><th>Item</th><th>UOM</th><th>Category</th><th>Sub-Category</th><th>Supplier ID</th>
                    @if(auth()->user()->canWriteInventory())<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->item_id }}</td>
                    <td>{{ $r->item }}</td>
                    <td>{{ $r->uom }}</td>
                    <td><span class="badge badge-stock">{{ $r->category }}</span></td>
                    <td>{{ $r->sub_category }}</td>
                    <td>{{ $r->supplier_id ?? '—' }} {{ $r->supplier->supplier ?? '' }}</td>
                    @if(auth()->user()->canWriteInventory())
                    <td class="actions-cell">
                        <a href="{{ route('items.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('items.destroy', $r) }}" onsubmit="return confirm('Delete this Item and related Item Branch rows?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No items.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
