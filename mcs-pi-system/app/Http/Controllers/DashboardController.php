<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'suppliers' => Supplier::count(),
            'items' => Item::count(),
            'pending_orders' => Order::where('status', 'Pending')->distinct('ge_number')->count('ge_number'),
            'low_stock' => ItemBranch::whereColumn('current_stock', '<=', 'reorder_level')->count(),
            'assets' => Asset::count(),
            'customers' => Customer::count(),
            'stock_value' => ItemBranch::select(DB::raw('COALESCE(SUM(current_stock * unit_cost), 0) as v'))->value('v'),
        ];
        return view('dashboard.index', compact('stats'));
    }
}
