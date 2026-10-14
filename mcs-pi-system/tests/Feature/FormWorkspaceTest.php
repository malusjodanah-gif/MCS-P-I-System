<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_and_edit_pages_offer_recoverable_navigation_and_font_controls(): void
    {
        $this->seed();
        $this->actingAs(User::findOrFail('admin'));
        foreach (['orders', 'receipts', 'items', 'item-branch', 'suppliers', 'customers', 'assets', 'issues', 'adjustments', 'users', 'branches'] as $section) {
            $this->get('/'.$section.'/create')->assertOk()
                ->assertSee('class="form-page"', false)
                ->assertSee('id="navigation-toggle"', false)
                ->assertSee('id="form-font-controls"', false)
                ;
            $this->assertMatchesRegularExpression('/<aside[^>]*id="app-navigation"[^>]*\\shidden(?:\\s|>)/', $this->get('/'.$section.'/create')->getContent());
        }
        $this->get('/items/1001/edit')->assertOk()->assertSee('class="form-page"', false);
        $this->get('/items')->assertOk()->assertDontSee('id="form-font-controls"', false)
            ->assertSee('id="navigation-toggle"', false);
    }
}
