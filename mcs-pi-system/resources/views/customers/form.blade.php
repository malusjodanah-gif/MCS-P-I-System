@extends('layouts.app')
@section('title', $row ? 'Edit Customer' : 'Add Customer')
@section('content')
<div class="card" style="max-width:520px">
    <form method="POST" action="{{ $row ? route('customers.update', $row) : route('customers.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>Customer ID (4001–4999)</label>
                <input type="number" name="customer_id" value="{{ old('customer_id', $nextId) }}" min="4001" max="4999">
            </div>
            @endif
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="customer" value="{{ old('customer', $row->customer ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="customer_type">
                    @foreach(['STAFF','STUDENT','MCS'] as $t)
                        <option value="{{ $t }}" @selected(old('customer_type', $row->customer_type ?? 'STAFF')===$t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $row->email ?? '') }}">
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
