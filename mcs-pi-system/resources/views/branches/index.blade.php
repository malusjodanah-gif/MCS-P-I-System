@extends('layouts.app')
@section('title', 'Branches')
@section('actions')
<a href="{{ route('branches.create') }}" class="btn btn-primary">Add Branch</a>
@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>Branch ID</th><th>Branch Name</th><th>Faculty</th><th>Head of School</th><th>Action</th></tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->branch_id }}</td>
                    <td>{{ $r->branch }}</td>
                    <td>{{ $r->faculty }}</td>
                    <td>{{ $r->hos ?? '—' }}</td>
                    <td class="actions-cell">
                        <a href="{{ route('branches.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        @if($r->branch_id !== 201)
                        <form method="POST" action="{{ route('branches.destroy', $r) }}" onsubmit="return confirm('Delete this branch?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No branches.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
