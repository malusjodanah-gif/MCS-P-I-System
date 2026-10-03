<?php
namespace App\Http\Controllers;
use App\Models\ItemBranch;
use App\Models\Issue;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function stockLevels()
    {
        $rows = ItemBranch::with('item')
            ->orderByRaw('(current_stock <= reorder_level) DESC')
            ->orderBy('item_id')
            ->get();
        return view('reports.stock', compact('rows'));
    }

    public function reorder()
    {
        $rows = ItemBranch::with('item')
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->get();
        return view('reports.reorder', compact('rows'));
    }

    public function purchases()
    {
        $rows = Order::select(
            'ge_number', 'inventory_flag', 'status', 'order_date', 'account_code',
            DB::raw('SUM(total_cost) as order_total'),
            DB::raw('COUNT(*) as line_count')
        )->groupBy('ge_number', 'inventory_flag', 'status', 'order_date', 'account_code')
         ->orderByDesc('order_date')
         ->get();
        return view('reports.purchases', compact('rows'));
    }

    public function issues()
    {
        $rows = Issue::with(['item', 'customer', 'user'])->orderByDesc('issue_date')->get();
        return view('reports.issues', compact('rows'));
    }
}
