<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\IdGenerator;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $rows = Customer::orderBy('customer_id')->get();
        return view('customers.index', compact('rows'));
    }

    public function create()
    {
        $nextId = IdGenerator::next('customers', 'customer_id', 4001, 4999);
        return view('customers.form', ['row' => null, 'nextId' => $nextId]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer|between:4001,4999|unique:customers,customer_id',
            'customer' => 'required|string|max:255',
            'customer_type' => 'required|in:STAFF,STUDENT,MCS',
            'email' => 'nullable|email|max:255',
        ]);
        IdGenerator::create('customers', 'customer_id', 4001, 4999, $data['customer_id'] ?? null,
            fn ($id) => Customer::create(array_replace($data, ['customer_id' => $id])));
        return redirect()->route('customers.index')->with('success', 'Customer created.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', ['row' => $customer, 'nextId' => null]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'customer' => 'required|string|max:255',
            'customer_type' => 'required|in:STAFF,STUDENT,MCS',
            'email' => 'nullable|email|max:255',
        ]);
        $customer->update($data);
        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
            return redirect()->route('customers.index')->with('success', 'Customer deleted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['delete' => 'Cannot delete: customer is referenced by issues.']);
        }
    }
}
