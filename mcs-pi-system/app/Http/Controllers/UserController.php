<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $rows = User::with('branch')->orderBy('user_id')->get();
        return view('users.index', compact('rows'));
    }

    public function create()
    {
        $branches = Branch::orderBy('branch_id')->get();
        return view('users.form', ['row' => null, 'branches' => $branches]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:4',
            'branch_id' => 'required|exists:branches,branch_id',
            'role' => ['required', Rule::in(User::ROLES)],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $branches = Branch::orderBy('branch_id')->get();
        return view('users.form', ['row' => $user, 'branches' => $branches]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'password' => 'nullable|string|min:4',
            'branch_id' => 'required|exists:branches,branch_id',
            'role' => ['required', Rule::in(User::ROLES)],
        ]);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->user_id === Auth::user()->user_id) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
