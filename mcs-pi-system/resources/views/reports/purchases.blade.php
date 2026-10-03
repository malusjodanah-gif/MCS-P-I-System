@extends('layouts.app')
@section('title', 'Report: purchases')
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Report data</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr><td><pre style="margin:0;font-size:0.8rem;white-space:pre-wrap">{{ json_encode($r->toArray(), JSON_PRETTY_PRINT) }}</pre></td></tr>
            @empty
                <tr><td class="text-muted">No data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-1"><a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">← Back</a></p>
</div>
@endsection
