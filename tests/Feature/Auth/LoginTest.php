<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_required_element_ids(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['username' => 'member', 'password' => 'secret']);

        Livewire::test(Login::class)
            ->set('username', 'member')
            ->set('password', 'secret')
            ->set('remember', true)
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'secret']);

        Livewire::test(Login::class)
            ->set('username', 'member')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors('username')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_login_requires_username_and_password(): void
    {
        Livewire::test(Login::class)
            ->call('login')
            ->assertHasErrors(['username' => 'required', 'password' => 'required']);
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/');
    }
}
