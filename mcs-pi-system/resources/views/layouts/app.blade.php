<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MCS P&I') — Purchasing & Inventory</title>
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
@php
    $u = auth()->user();
    $isFormPage = request()->routeIs('*.create', '*.edit');
@endphp
<body @class(['form-page' => $isFormPage])>
<noscript><style>.sidebar[hidden] { display: flex !important; }</style></noscript>
<div class="account-bar">
    <span>{{ $u->name }} · {{ $u->role }}</span>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-secondary">Sign out</button>
    </form>
</div>
<div class="app-shell">
    <aside id="app-navigation" class="sidebar" aria-label="Main navigation" @if($isFormPage) hidden @endif>
        <div class="sidebar-brand">
            <h1>MCS P&amp;I System</h1>
            <small>Purchasing &amp; Inventory</small>
        </div>

        <div class="nav-section">Overview</div>
        <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>

        <div class="nav-section">Purchasing</div>
        <a class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">GE Orders</a>
        <a class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">Suppliers</a>
        <a class="nav-item {{ request()->routeIs('receipts.*') ? 'active' : '' }}" href="{{ route('receipts.index') }}">Purchase Receipts</a>

        <div class="nav-section">Inventory</div>
        <a class="nav-item {{ request()->routeIs('items.*') ? 'active' : '' }}" href="{{ route('items.index') }}">Items</a>
        <a class="nav-item {{ request()->routeIs('itembranch.*') ? 'active' : '' }}" href="{{ route('itembranch.index') }}">Item Branch</a>
        <a class="nav-item {{ request()->routeIs('issues.*') ? 'active' : '' }}" href="{{ route('issues.index') }}">Issue</a>
        <a class="nav-item {{ request()->routeIs('adjustments.*') ? 'active' : '' }}" href="{{ route('adjustments.index') }}">Stock Adjustments</a>
        <a class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">Customers</a>
        <a class="nav-item {{ request()->routeIs('assets.*') ? 'active' : '' }}" href="{{ route('assets.index') }}">Assets</a>

        <div class="nav-section">Reports</div>
        <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>

        @if($u->isAdmin())
        <div class="nav-section">Administration</div>
        <a class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Users</a>
        <a class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">Branches</a>
        @endif

        <div class="sidebar-footer">
            <div class="user-meta">
                <strong>{{ $u->name }}</strong>
                <span>{{ $u->role }} · Branch {{ $u->branch_id }}</span>
            </div>

        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="workspace-heading">
                <button id="navigation-toggle" type="button" class="btn btn-secondary" aria-controls="app-navigation" aria-expanded="{{ $isFormPage ? 'false' : 'true' }}" hidden>{{ $isFormPage ? 'Show menu' : 'Hide menu' }}</button>
            <h2>@yield('title', 'Dashboard')</h2>
            </div>
            <div class="workspace-tools">
                @if($isFormPage)
                <div id="form-font-controls" class="btn-group" role="group" aria-label="Form text size" hidden>
                    <button id="form-font-smaller" type="button" class="btn btn-secondary" aria-label="Decrease form text size">A−</button>
                    <output id="form-font-size" role="status" aria-live="polite">14px</output>
                    <button id="form-font-larger" type="button" class="btn btn-secondary" aria-label="Increase form text size">A+</button>
                    <button id="form-font-reset" type="button" class="btn btn-secondary">Reset size</button>
                </div>
                @endif
                <div class="btn-group">@yield('actions')</div>
            </div>
        </header>
        <main class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0;padding-left:1.1rem">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('js/table-browser.js') }}" defer></script>
<script src="{{ asset('js/form-workspace.js') }}" defer></script>
</body>
</html>

