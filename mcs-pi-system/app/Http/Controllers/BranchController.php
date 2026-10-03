<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $rows = Branch::orderBy('branch_id')->get();
        return view('branches.index', compact('rows'));
    }

    public function create()
    {
        return view('branches.form', ['row' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|between:201,299|unique:branches,branch_id',
            'branch' => 'required|string|max:255',
            'faculty' => 'required|string|max:255',
            'hos' => 'nullable|string|max:255',
        ]);
        Branch::create($data);
        return redirect()->route('branches.index')->with('success', 'Branch created. HoS is informational only.');
    }

    public function edit(Branch $branch)
    {
        return view('branches.form', ['row' => $branch]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'branch' => 'required|string|max:255',
            'faculty' => 'required|string|max:255',
            'hos' => 'nullable|string|max:255',
        ]);
        $branch->update($data);
        return redirect()->route('branches.index')->with('success', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->branch_id === 201) {
            return back()->withErrors(['delete' => 'Cannot delete the default MCS branch (201).']);
        }
        try {
            $branch->delete();
            return redirect()->route('branches.index')->with('success', 'Branch deleted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['delete' => 'Cannot delete: branch is referenced by other records.']);
        }
    }
}
