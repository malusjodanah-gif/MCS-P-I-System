@extends('layouts.app')
@section('title', 'Create Issue')
@section('content')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('issues.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Issue Number (7001–7999)</label>
                <input type="number" name="issue_number" value="{{ old('issue_number', $nextId) }}" min="7001" max="7999">
            </div>
            <div class="form-group">
                <label>Item</label>
                <select name="item_id" required>
                    @foreach($items as $i)
                        @php $avail = $stock[$i->item_id]->current_stock ?? 0; @endphp
                        <option value="{{ $i->item_id }}">{{ $i->item_id }} — {{ $i->item }} (available: {{ $avail }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required>
            </div>
            <div class="form-group">
                <label>Customer</label>
                <select name="customer_id" required>
                    @foreach($customers as $c)
                        <option value="{{ $c->customer_id }}">{{ $c->customer }} ({{ $c->customer_type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group full">
                <label>Purpose</label>
                <input type="text" name="purpose" value="{{ old('purpose') }}">
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Issue</button>
            <a href="{{ route('issues.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
