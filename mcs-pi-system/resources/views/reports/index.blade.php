@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="card">
    <div class="card-header"><span class="card-title">Available Reports</span></div>
    <div class="btn-group">
        <a class="btn btn-primary" href="{{ route('reports.stock') }}">Stock Levels</a>
        <a class="btn btn-primary" href="{{ route('reports.reorder') }}">Reorder List</a>
        <a class="btn btn-primary" href="{{ route('reports.purchases') }}">Purchase Summary</a>
        <a class="btn btn-primary" href="{{ route('reports.issues') }}">Issue History</a>
    </div>
</div>
@endsection
