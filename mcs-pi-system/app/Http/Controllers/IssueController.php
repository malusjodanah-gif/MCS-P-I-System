<?php

namespace App\Http\Controllers;

use App\Support\IdGenerator;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemBranch;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $stock = ItemBranch::where('branch_id', 201)->get()->keyBy('item_id');
        $nextId = IdGenerator::next('issues', 'issue_number', 7001, 7999);
        $branches = Branch::orderBy('branch_id')->get();
        return view('issues.form', compact('items', 'customers', 'stock', 'nextId', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'issue_number' => 'nullable|integer|between:7001,7999|unique:issues,issue_number',
            'item_id' => 'required|exists:items,item_id',
            'branch_id' => 'nullable|integer|exists:branches,branch_id',
            'quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,customer_id',
            'purpose' => 'nullable|string|max:500',
        ]);

        $branchId = $data['branch_id'] ?? 201;
        $qty = (int) $data['quantity'];

        try {
        IdGenerator::create('issues', 'issue_number', 7001, 7999, $data['issue_number'] ?? null, function ($num) use ($data, $branchId, $qty) {
            $ib = ItemBranch::where('item_id', $data['item_id'])->where('branch_id', $branchId)->lockForUpdate()->first();
            if (!$ib) {
                throw new \RuntimeException('Item is not stocked at this branch.');
            }
            if ($ib->current_stock < $qty) {
                throw new \RuntimeException("Insufficient stock. Available: {$ib->current_stock}");
            }

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
            $issue = Issue::whereKey($issue->getKey())->lockForUpdate()->firstOrFail();
            $ib = ItemBranch::where('item_id', $issue->item_id)->where('branch_id', $issue->branch_id)->lockForUpdate()->first();
            if (!$ib) {
                throw ValidationException::withMessages(['delete' => 'The Item Branch is missing; cancellation cannot restore stock.']);
            }
            $ib->current_stock = $ib->current_stock + $issue->quantity;
            $ib->save();
            $issue->delete();
        });
        return redirect()->route('issues.index')->with('success', 'Issue cancelled and stock restored.');
    }

    public function edit(Issue $issue)
    {
        return view('issues.form', [
            'row' => $issue, 'nextId' => $issue->issue_number,
            'items' => Item::orderBy('item')->get(),
            'customers' => Customer::orderBy('customer')->get(),
            'branches' => Branch::orderBy('branch_id')->get(),
        ]);
    }

    public function update(Request $request, Issue $issue)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'branch_id' => 'required|exists:branches,branch_id',
            'quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,customer_id',
            'purpose' => 'nullable|string|max:500',
        ]);
        DB::transaction(function () use ($issue, $data) {
            $issue = Issue::whereKey($issue->getKey())->lockForUpdate()->firstOrFail();
            $rows = ItemBranch::where(function ($query) use ($issue, $data) {
                $query->where(fn ($q) => $q->where('item_id', $issue->item_id)->where('branch_id', $issue->branch_id))
                    ->orWhere(fn ($q) => $q->where('item_id', $data['item_id'])->where('branch_id', $data['branch_id']));
            })->orderBy('item_id')->orderBy('branch_id')->lockForUpdate()->get();
            $old = $rows->first(fn ($r) => $r->item_id == $issue->item_id && $r->branch_id == $issue->branch_id);
            $new = $rows->first(fn ($r) => $r->item_id == $data['item_id'] && $r->branch_id == $data['branch_id']);
            if (!$old || !$new) {
                throw ValidationException::withMessages(['item_id' => 'Both the original and selected Item Branch must exist.']);
            }
            $old->current_stock += $issue->quantity;
            if ($new->current_stock < $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock for the revised issue.']);
            }
            $new->current_stock -= $data['quantity'];
            $old->save();
            if ($new !== $old) {
                $new->save();
            }
            $data['uom'] = $new->uom;
            $issue->update($data);
        });
        return redirect()->route('issues.index')->with('success', 'Issue updated and stock reconciled.');
    }
}
