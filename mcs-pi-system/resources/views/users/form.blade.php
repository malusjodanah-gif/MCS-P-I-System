@extends('layouts.app')
@section('title', $row ? 'Edit user' : 'Add user')
@section('content')
<div class="card">
    <form method="POST" action="{{ $row ? route('users.update', $row) : route('users.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>User ID</label>
                <input type="text" name="user_id" value="{{ old('user_id') }}" required>
            </div>
            @else
            <div class="form-group">
                <label>User ID</label>
                <input type="text" value="{{ $row->user_id }}" disabled>
            </div>
            @endif
            <div class="form-group">
                <label>User</label>
                <input type="text" name="name" value="{{ old('name', $row->name ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $row->email ?? '') }}">
            </div>
            <div class="form-group">
                <label>Branch ID</label>
                <select name="branch_id" required>
                    @foreach($branches as $b)
                        <option value="{{ $b->branch_id }}" @selected(old('branch_id', $row->branch_id ?? 201)==$b->branch_id)>{{ $b->branch_id }} — {{ $b->branch }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    @foreach(\App\Models\User::ROLES as $role)
                        <option value="{{ $role }}" @selected(old('role', $row->role ?? 'EndUser')===$role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Password {{ $row ? '(leave blank to keep)' : '' }}</label>
                <input type="password" name="password" {{ $row ? '' : 'required' }} minlength="4">
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
