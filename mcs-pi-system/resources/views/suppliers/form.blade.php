@extends('layouts.app')
@section('title', $row ? 'Edit Supplier' : 'Add Supplier')
@section('content')
<div class="card" style="max-width:640px">
    <form method="POST" action="{{ $row ? route('suppliers.update', $row) : route('suppliers.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>Supplier ID (5001–5999)</label>
                <input type="number" name="supplier_id" value="{{ old('supplier_id', $nextId) }}" min="5001" max="5999">
            </div>
            @endif
            <div class="form-group">
                <label>Supplier</label>
                <input type="text" name="supplier" value="{{ old('supplier', $row->supplier ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Contact</label>
                <input type="text" name="contact" value="{{ old('contact', $row->contact ?? '') }}">
            </div>
            <div class="form-group">
                <label>Payment term</label>
                <select name="payment_term">
                    <option value="CREDIT" @selected(old('payment_term', $row->payment_term ?? 'CREDIT')==='CREDIT')>CREDIT</option>
                    <option value="CASH" @selected(old('payment_term', $row->payment_term ?? '')==='CASH')>CASH</option>
                </select>
            </div>
            <div class="form-group">
                <label>Currency</label>
                <input type="text" name="currency" value="{{ old('currency', $row->currency ?? 'PGK') }}" required>
            </div>
            <div class="form-group full">
                <label>Address</label>
                <input type="text" name="address" value="{{ old('address', $row->address ?? '') }}">
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
