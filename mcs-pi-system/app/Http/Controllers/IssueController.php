<?php

namespace App\Http\Controllers;

use App\Support\IdGenerator;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemBranch;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IssueController extends Controller
{
    public function index()
    {
        $rows = Issue::with(['item', 'customer', 'user'])->orderByDesc('issue_date')->orderByDesc('issue_number')->get();
        return view('issues.index', compact('rows'));
    }

    public function create()
    {
        $items = Item::orderBy('item')->get();
        $customers = Customer::orderBy('customer')->get();
        $stock = ItemBranch::all()->keyBy('item_id');
        $nextId = IdGenerator::next('issues', 'issue_number', 7001, 7999);
        return view('issues.form', compact('items', 'customers', 'stock', 'nextId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'issue_number' => 'nullable|integer|between:7001,7999|unique:issues,issue_number',
            'item_id' => 'required|exists:items,item_id',
            'branch_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,customer_id',
            'purpose' => 'nullable|string|max:500',
        ]);

        $branchId = $data['branch_id'] ?? 201;
        $qty = (int) $data['quantity'];

        try {
        DB::transaction(function () use ($data, $branchId, $qty) {
            $ib = ItemBranch::where('item_id', $data['item_id'])->where('branch_id', $branchId)->lockForUpdate()->first();
            if (!$ib) {
                throw new \RuntimeException('Item is not stocked at this branch.');
            }
            if ($ib->current_stock < $qty) {
                throw new \RuntimeException("Insufficient stock. Available: {$ib->current_stock}");
            }

            $num = $data['issue_number'] ?? IdGenerator::next('issues', 'issue_number', 7001, 7999);
            Issue::create([
                'issue_number' => $num,
                'branch_id' => $branchId,
                'item_id' => $data['item_id'],
                'quantity' => $qty,
                'uom' => $ib->uom,
                'customer_id' => $data['customer_id'],
                'purpose' => $data['purpose'] ?? null,
                'user_id' => Auth::user()->user_id,
                'issue_date' => now()->toDateString(),
            ]);
            $ib->current_stock = $ib->current_stock - $qty;
            $ib->save();
        });

        return redirect()->route('issues.index')->with('success', 'Stock issued.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Issue $issue)
    {
        // Reversing an issue restores stock
        DB::transaction(function () use ($issue) {
            $ib = ItemBranch::where('item_id', $issue->item_id)->where('branch_id', $issue->branch_id)->lockForUpdate()->first();
            if ($ib) {
                $ib->current_stock = $ib->current_stock + $issue->quantity;
                $ib->save();
            }
            $issue->delete();
        });
        return redirect()->route('issues.index')->with('success', 'Issue cancelled and stock restored.');
    }
}
