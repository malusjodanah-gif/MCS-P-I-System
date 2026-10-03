@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="stats-grid">
    <div class="stat-card"><div class="label">Suppliers</div><div class="value">{{ $stats['suppliers'] }}</div></div>
    <div class="stat-card"><div class="label">Stock items</div><div class="value">{{ $stats['items'] }}</div></div>
    <div class="stat-card {{ $stats['pending_orders'] ? 'warning' : '' }}"><div class="label">Pending GEs</div><div class="value">{{ $stats['pending_orders'] }}</div></div>
    <div class="stat-card {{ $stats['low_stock'] ? 'danger' : 'success' }}"><div class="label">Low stock</div><div class="value">{{ $stats['low_stock'] }}</div></div>
    <div class="stat-card"><div class="label">Assets</div><div class="value">{{ $stats['assets'] }}</div></div>
    <div class="stat-card"><div class="label">Customers</div><div class="value">{{ $stats['customers'] }}</div></div>
    <div class="stat-card"><div class="label">Stock value (PGK)</div><div class="value">{{ number_format($stats['stock_value'], 2) }}</div></div>
</div>
<div class="card">
    <div class="card-header"><span class="card-title">Session</span></div>
    <p class="text-muted">Signed in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}), branch {{ auth()->user()->branch_id }}.</p>
    <p class="mt-1 text-muted" style="font-size:0.85rem">
        GE approval is manual — status is updated by Purchasing on the GE Orders screen.
        Head of School (HoS) on Branch is informational only and is not a system role.
    </p>
</div>
<div class="card">
    <div class="card-header"><span class="card-title">Quick links</span></div>
    <div class="btn-group">
        @if(auth()->user()->canWritePurchasing())
            <a class="btn btn-primary" href="{{ route('orders.create') }}">New GE order</a>
        @endif
        @if(auth()->user()->canWriteInventory())
            <a class="btn btn-primary" href="{{ route('issues.create') }}">Issue stock</a>
        @endif
        <a class="btn btn-secondary" href="{{ route('itembranch.index') }}">Stock Levels</a>
        <a class="btn btn-secondary" href="{{ route('reports.index') }}">Reports</a>
    </div>
</div>
@endsection
