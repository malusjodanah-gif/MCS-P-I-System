<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ItemBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryBrowseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Branch::create(['branch_id' => 202, 'branch' => 'Alpha search branch', 'faculty' => 'Sciences']);
        ItemBranch::create(['item_id' => 1001, 'branch_id' => 202, 'uom' => 'REAM',
            'current_stock' => 0, 'unit_cost' => 5, 'location' => '100% guarded shelf',
            'reorder_level' => 0, 'reorder_quantity' => 10]);
    }

    public function test_all_four_roles_can_search_and_combine_itembranch_filters(): void
    {
        foreach (['admin', 'po1', 'io1', 'eu1'] as $id) {
            $this->actingAs(User::findOrFail($id))->get('/item-branch?q=A4&branch_id=202&category=CONSUMABLE&stock=low')
                ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->branch_id === 202);
        }
    }

    public function test_search_treats_percent_as_literal_and_stock_filters_do_not_change_balances(): void
    {
        $before = ItemBranch::sum('current_stock');
        $this->actingAs(User::findOrFail('eu1'))->get('/item-branch?q=%25')
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->branch_id === 202);
        $this->get('/item-branch?branch_id=202&stock=positive')->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
        $this->assertEquals($before, ItemBranch::sum('current_stock'));
    }

    public function test_enduser_tables_have_no_action_headers_and_writers_retain_controls(): void
    {
        $this->actingAs(User::findOrFail('eu1'));
        foreach (['orders', 'items', 'item-branch', 'suppliers', 'receipts', 'issues', 'adjustments', 'customers', 'assets'] as $path) {
            $response = $this->get('/'.$path)->assertOk();
            $this->assertDoesNotMatchRegularExpression('/<th[^>]*>\s*Actions?\s*<\/th>/i', $response->getContent());
        }
        $this->actingAs(User::findOrFail('po1'))->get('/orders')->assertSee('<th>Action</th>', false);
    }
}
