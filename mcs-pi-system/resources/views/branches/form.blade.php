@extends('layouts.app')
@section('title', $row ? 'Edit Branch' : 'Add Branch')
@section('content')
<div class="card" style="max-width:520px">
    <form method="POST" action="{{ $row ? route('branches.update', $row) : route('branches.store') }}">
        @csrf
        @if($row) @method('PUT') @endif
        <div class="form-grid">
            @if(!$row)
            <div class="form-group">
                <label>Branch ID (201–299)</label>
                <input type="number" name="branch_id" value="{{ old('branch_id') }}" min="201" max="299" required>
            </div>
            @endif
            <div class="form-group">
                <label>Branch name</label>
                <input type="text" name="branch" value="{{ old('branch', $row->branch ?? 'School of MCS') }}" required>
            </div>
            <div class="form-group">
                <label>Faculty</label>
                <input type="text" name="faculty" value="{{ old('faculty', $row->faculty ?? 'Faculty of Sciences') }}" required>
            </div>
            <div class="form-group full">
                <label>Head of School</label>
                <input type="text" name="hos" value="{{ old('hos', $row->hos ?? '') }}">
            </div>
        </div>
        <div class="mt-2 btn-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
