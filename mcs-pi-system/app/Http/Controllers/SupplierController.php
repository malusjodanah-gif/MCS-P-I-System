<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Support\IdGenerator;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $rows = Supplier::orderBy('supplier_id')->get();
        return view('suppliers.index', compact('rows'));
    }

    public function create()
    {
        $nextId = IdGenerator::next('suppliers', 'supplier_id', 5001, 5999);
        return view('suppliers.form', ['row' => null, 'nextId' => $nextId]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|integer|between:5001,5999|unique:suppliers,supplier_id',
            'supplier' => 'required|string|max:255',
            'address' => 'nullable|string',
            'contact' => 'nullable|string|max:255',
            'payment_term' => 'required|in:CASH,CREDIT',
            'currency' => 'required|string|max:10',
        ]);
        IdGenerator::create('suppliers', 'supplier_id', 5001, 5999, $data['supplier_id'] ?? null,
            fn ($id) => Supplier::create(array_replace($data, ['supplier_id' => $id])));
        return redirect()->route('suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', ['row' => $supplier, 'nextId' => null]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'supplier' => 'required|string|max:255',
            'address' => 'nullable|string',
            'contact' => 'nullable|string|max:255',
            'payment_term' => 'required|in:CASH,CREDIT',
            'currency' => 'required|string|max:10',
        ]);
        $supplier->update($data);
        return redirect()->route('suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
            return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['delete' => 'Cannot delete: supplier is referenced by existing records.']);
        }
    }
}
