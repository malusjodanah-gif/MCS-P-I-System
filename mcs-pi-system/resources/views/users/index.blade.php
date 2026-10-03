@extends('layouts.app')
@section('title', 'Users')
@section('actions')
<a href="{{ route('users.create') }}" class="btn btn-primary">Add User</a>
@endsection
@section('content')
<div class="card">
    <p class="text-muted mb-1">Manage system access and assign purchasing or inventory roles for each staff member.</p>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>User ID</th><th>Name</th><th>Email</th><th>Branch</th><th>Role</th><th></th></tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->user_id }}</td>
                    <td>{{ $r->name }}</td>
                    <td>{{ $r->email ?? '—' }}</td>
                    <td>{{ $r->branch_id }}</td>
                    <td>{{ $r->role }}</td>
                    <td class="actions-cell">
                        <a href="{{ route('users.edit', $r) }}" class="btn btn-sm btn-secondary">Edit</a>
                        @if($r->user_id !== auth()->user()->user_id)
                        <form method="POST" action="{{ route('users.destroy', $r) }}" onsubmit="return confirm('Delete this user?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No users.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
