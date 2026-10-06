<?php

namespace Tests\Feature;

use App\Models\ItemBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlphaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_stock_can_be_established_by_adjustment_then_issued_and_restored(): void
    {
        $this->seed();
        $row = ItemBranch::where('item_id', 1001)->where('branch_id', 201)->firstOrFail();
        $row->current_stock = 0;
        $row->save();
        $this->actingAs(User::findOrFail('io1'))->post('/adjustments', [
            'adjustment_number' => 5998, 'adjustment_type' => 'IN', 'item_id' => 1001,
            'branch_id' => 201, 'quantity' => 10, 'purpose' => 'Alpha opening stock',
        ])->assertRedirect(route('adjustments.index'));
        $this->assertSame(10, $row->fresh()->current_stock);
        $this->post('/issues', ['issue_number' => 7998, 'item_id' => 1001,
            'branch_id' => 201, 'quantity' => 2, 'customer_id' => 4001,
            'purpose' => 'Alpha issue'])->assertRedirect(route('issues.index'));
        $this->assertSame(8, $row->fresh()->current_stock);
        $this->delete('/issues/7998')->assertRedirect(route('issues.index'));
        $this->assertSame(10, $row->fresh()->current_stock);
        $this->assertDatabaseMissing('issues', ['issue_number' => 7998]);
    }

    public function test_signout_is_outside_the_mobile_hidden_footer_and_invalidates_session(): void
    {
        $this->seed();
        $response = $this->actingAs(User::findOrFail('eu1'))->get('/dashboard')->assertOk();
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'class="app-shell"'), strpos($html, 'Sign out'));
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_ge_table_exposes_specification_identifiers(): void
    {
        $this->seed();
        $this->actingAs(User::findOrFail('eu1'))->get('/orders')->assertOk()
            ->assertSee('Item ID')->assertSee('PO Number')->assertSee('Branch ID')
            ->assertSee('Inventory Flag')->assertSee('Account Code')->assertSee('Quantity');
    }
}
