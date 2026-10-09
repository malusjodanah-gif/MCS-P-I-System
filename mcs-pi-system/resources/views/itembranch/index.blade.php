@extends('layouts.app')
@section('title', 'Item Branch')
@section('actions')
@if(auth()->user()->canWriteInventory())
<a href="{{ route('itembranch.create') }}" class="btn btn-primary">Add Item Branch</a>
@endif
@endsection
@section('content')
<div class="card">
    <form method="GET" action="{{ route('itembranch.index') }}" class="filters" aria-label="Filter Item Branch records">
        <div class="form-group"><label for="stock-search">Search item, ID, location or branch</label><input id="stock-search" type="search" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="form-group"><label for="stock-branch">Branch</label><select id="stock-branch" name="branch_id"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->branch_id }}" @selected(($filters['branch_id'] ?? '')==$branch->branch_id)>{{ $branch->branch_id }} — {{ $branch->branch }}</option>@endforeach</select></div>
        <div class="form-group"><label for="stock-category">Category</label><select id="stock-category" name="category"><option value="">All categories</option>@foreach(['ASSET', 'CONSUMABLE'] as $category)<option @selected(($filters['category'] ?? '')===$category)>{{ $category }}</option>@endforeach</select></div>
        <div class="form-group"><label for="stock-level">Stock availability</label><select id="stock-level" name="stock"><option value="">All stock levels</option><option value="low" @selected(($filters['stock'] ?? '')==='low')>Reorder needed (stock ≤ level)</option><option value="positive" @selected(($filters['stock'] ?? '')==='positive')>In stock (greater than zero)</option><option value="zero" @selected(($filters['stock'] ?? '')==='zero')>Zero stock</option></select></div>
        <div class="btn-group"><button class="btn btn-primary" type="submit">Search / Filter</button><a href="{{ route('itembranch.index') }}" class="btn btn-secondary">Reset</a></div>
    </form>
    <p class="text-muted mb-1">{{ $rows->count() }} matching record(s).</p>
    <div class="table-wrap">
        <table class="data" data-table-browser="off">
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
                    <td>{{ $r->reorder_level }}{{ $r->needsReorder() ? ' (reorder needed)' : '' }}</td>
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
                <tr><td colspan="{{ auth()->user()->canWriteInventory() ? 9 : 8 }}" class="text-muted">No Item Branch records. Add an Item Branch record for each item and branch that needs stock tracking.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

