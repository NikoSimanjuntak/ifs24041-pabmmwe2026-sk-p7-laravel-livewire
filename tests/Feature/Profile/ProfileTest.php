<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create(['name' => 'Budi', 'username' => 'budi', 'password' => 'lama123']);
    }

    private function component()
    {
        return Livewire::actingAs($this->user)->test(Profile::class);
    }

    public function test_page_renders_with_current_data(): void
    {
        $this->actingAs($this->user)->get('/profile')->assertOk()->assertSeeLivewire(Profile::class);

        $this->component()->assertSet('name', 'Budi')->assertSet('username', 'budi');
    }

    public function test_update_profile(): void
    {
        $this->component()
            ->set('name', 'Budi Baru')
            ->set('username', 'budi_baru')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSee('Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Budi Baru', 'username' => 'budi_baru']);
    }

    public function test_update_profile_allows_own_username_but_not_others(): void
    {
        User::factory()->create(['username' => 'dipakai']);

        $this->component()->set('username', 'budi')->call('updateProfile')->assertHasNoErrors();
        $this->component()->set('username', 'dipakai')->set('name', '')->call('updateProfile')->assertHasErrors(['username', 'name']);
    }

    public function test_update_password(): void
    {
        $this->component()
            ->set('current_password', 'lama123')
            ->set('password', 'baru1234')
            ->set('password_confirmation', 'baru1234')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('password', '')
            ->assertSee('Password berhasil diubah.');

        $this->assertTrue(Hash::check('baru1234', $this->user->refresh()->password));
    }

    public function test_update_password_validation(): void
    {
        $this->component()
            ->set('current_password', 'salah')
            ->set('password', '123')
            ->set('password_confirmation', 'beda')
            ->call('updatePassword')
            ->assertHasErrors(['current_password', 'password']);
    }

    public function test_update_photo_replaces_old_photo(): void
    {
        $this->component()->set('photo', UploadedFile::fake()->image('a.jpg'))->call('updatePhoto')->assertHasNoErrors();
        $first = $this->user->refresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $this->component()->set('photo', UploadedFile::fake()->image('b.jpg'))->call('updatePhoto')->assertSee('Foto profil berhasil diperbarui.');
        $second = $this->user->refresh()->profile_photo_path;

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_update_photo_validation(): void
    {
        $this->component()->call('updatePhoto')->assertHasErrors(['photo' => 'required']);
        $this->component()->set('photo', UploadedFile::fake()->create('x.pdf', 5))->call('updatePhoto')->assertHasErrors('photo');
    }
}
