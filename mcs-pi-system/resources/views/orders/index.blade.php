@extends('layouts.app')
@section('title', 'GE Orders')
@section('actions')
    @if(auth()->user()->canWritePurchasing())
        <a href="{{ route('orders.create') }}" class="btn btn-primary">New GE Order</a>
    @endif
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>GE Number</th><th>Inventory Flag</th><th>Supplier</th><th>Date</th><th>PO Number</th><th>Branch ID</th><th>Account Code</th><th>Item ID</th><th>Item</th>
                    <th>Quantity</th><th>UOM</th><th>Unit Cost</th><th>Total Cost</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $ge => $lines)
                @foreach($lines as $idx => $r)
                <tr>
                    <td>@if($idx===0)<strong>{{ $r->ge_number }}</strong>@endif</td>
                    <td>@if($idx===0)<span class="badge {{ $r->inventory_flag === 'STOCK' ? 'badge-stock' : 'badge-non-stock' }}">{{ $r->inventory_flag }}</span>@endif</td>
                    <td>@if($idx===0){{ $r->supplier->supplier ?? $r->supplier_id }}@endif</td>
                    <td>@if($idx===0){{ $r->order_date->format('Y-m-d') }}@endif</td>
                    <td>{{ $r->po_number ?? '—' }}</td>
                    <td>{{ $r->branch_id }}</td>
                    <td>{{ $r->account_code ?? '—' }}</td>
                    <td>{{ $r->item_id ?? '—' }}</td>
                    <td>{{ $r->item }}</td>
                    <td>{{ $r->quantity }}</td><td>{{ $r->uom }}</td>
                    <td>{{ number_format($r->unit_cost, 2) }}</td>
                    <td>{{ number_format($r->total_cost, 2) }}</td>
                    <td><span class="badge badge-{{ strtolower($r->status) }}">{{ $r->status }}</span></td>
                    <td>
                        @if(auth()->user()->canWritePurchasing())
                        @if($r->status === 'Pending')
                        <a href="{{ route('orders.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        @endif
                        <form method="POST" action="{{ route('orders.status', $r) }}" style="display:inline-flex;gap:0.35rem;align-items:center">
                            @csrf @method('PATCH')
                            <select name="status" style="font-size:0.75rem;padding:0.2rem">
                                @foreach(\App\Models\Order::STATUSES as $s)
                                    <option value="{{ $s }}" @selected($s === $r->status)>{{ $s }}</option>
                                @endforeach
                            </select>
                            <label style="font-size:0.7rem;white-space:nowrap"><input type="checkbox" name="update_whole_ge" value="1"> whole GE</label>
                            <button class="btn btn-sm btn-secondary" type="submit">Set</button>
                        </form>
                        @if(in_array($r->status, ['Pending','Rejected','Cancelled']))
                        <form method="POST" action="{{ route('orders.destroy', $r) }}" style="display:inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Del</button>
                        </form>
                        @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="16" class="text-muted">No GE orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
