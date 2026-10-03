@extends('layouts.app')
@php $row = $row ?? null; @endphp
@section('title', $row ? 'Edit Issue' : 'Create Issue')
@section('content')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ $row ? route('issues.update', $row) : route('issues.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-group">
                <label>Issue Number (7001–7999)</label>
                <input type="number" name="issue_number" @disabled($row) value="{{ old('issue_number', $nextId) }}" min="7001" max="7999">
            </div>
            <div class="form-group">
                <label>Item</label>
                <select name="item_id" required>
                    @foreach($items as $i)
                        <option value="{{ $i->item_id }}" @selected(old('item_id', $row->item_id ?? null)==$i->item_id)>{{ $i->item_id }} — {{ $i->item }} </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="{{ old('quantity', $row->quantity ?? 1) }}" min="1" required>
            </div>
            <div class="form-group">
                <label>Customer</label>
                <select name="customer_id" required>
                    @foreach($customers as $c)
                        <option value="{{ $c->customer_id }}" @selected(old('customer_id', $row->customer_id ?? null)==$c->customer_id)>{{ $c->customer }} ({{ $c->customer_type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group full">
                <label>Purpose</label>
                <input type="text" name="purpose" value="{{ old('purpose', $row->purpose ?? '') }}">
            </div>
            <div class="form-group">
                <label>Branch</label>
                <select name="branch_id" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->branch_id }}" @selected(old('branch_id', $row->branch_id ?? 201)==$branch->branch_id)>{{ $branch->branch_id }} — {{ $branch->branch }}</option>
                    @endforeach
                </select>
                <small>Stock is checked at the selected branch when saved.</small>
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">{{ $row ? 'Save Issue' : 'Issue' }}</button>
            <a href="{{ route('issues.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
