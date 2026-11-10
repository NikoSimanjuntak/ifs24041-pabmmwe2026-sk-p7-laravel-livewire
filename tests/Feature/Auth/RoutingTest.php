<?php

namespace Tests\Feature\Auth;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_root_redirects_member_to_stocks(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('stocks.index'));
    }

    public function test_root_redirects_admin_to_users(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect(route('admin.users'));
    }

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $this->get('/stocks')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    public function test_logout_clears_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_cannot_open_member_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->actingAs($admin)->get('/stocks/'.$stock->id)->assertForbidden();
    }

    public function test_member_cannot_open_admin_routes(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get('/admin/users/'.$member->id.'/stocks')->assertForbidden();
    }

    public function test_layout_shows_navigation_for_each_role(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Siti Member']))
            ->get('/stocks')->assertOk()->assertSee('Siti Member')->assertSee('Stok');

        $this->actingAs(User::factory()->admin()->create(['name' => 'Bos Admin']))
            ->get('/admin/users')->assertOk()->assertSee('Bos Admin')->assertSee('Users');
    }
}
