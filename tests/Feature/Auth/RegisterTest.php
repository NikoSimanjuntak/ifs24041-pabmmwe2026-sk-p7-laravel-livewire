<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee('Register');
    }

    public function test_user_can_register_as_member_and_is_logged_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi')
            ->set('username', 'budi_01')
            ->set('password', 'rahasia')
            ->set('password_confirmation', 'rahasia')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $user = User::where('username', 'budi_01')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['username' => 'dipakai']);

        Livewire::test(Register::class)
            ->set('name', '')
            ->set('username', 'dipakai')
            ->set('password', '123')
            ->set('password_confirmation', 'beda')
            ->call('register')
            ->assertHasErrors(['name', 'username', 'password']);
    }
}
