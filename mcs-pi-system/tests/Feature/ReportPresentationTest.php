<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enduser_reports_render_named_columns_instead_of_json(): void
    {
        $this->seed();
        $this->actingAs(User::findOrFail('eu1'));
        foreach (['stock' => 'Current Stock', 'reorder' => 'Reorder Quantity',
            'purchases' => 'GE Number', 'issues' => 'Issue Number'] as $report => $heading) {
            $this->get('/reports/'.$report)->assertOk()->assertSee($heading)
                ->assertDontSee('<pre', false)->assertDontSee('Report data');
        }
        $this->get('/reports/stock')->assertSee('Stock Value')->assertSee('A4 Copy Paper');
    }

    public function test_purchase_summary_keeps_different_suppliers_separate(): void
    {
        $this->seed();
        $base = ['ge_number' => 9876, 'po_number' => 9876, 'inventory_flag' => 'NON-STOCK',
            'order_date' => '2026-10-06', 'branch_id' => 201, 'user_id' => 'po1',
            'account_code' => 'ALPHA', 'item' => 'Service', 'quantity' => 1,
            'unit_cost' => 10, 'total_cost' => 10, 'status' => 'Ordered'];
        Order::create($base + ['supplier_id' => 5001]);
        Order::create($base + ['supplier_id' => 5002]);
        $this->actingAs(User::findOrFail('eu1'))->get('/reports/purchases')
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->where('ge_number', 9876)->count() === 2);
    }

    public function test_reports_require_authentication(): void
    {
        foreach (['stock', 'reorder', 'purchases', 'issues'] as $report) {
            $this->get('/reports/'.$report)->assertRedirect('/login');
        }
    }
}
