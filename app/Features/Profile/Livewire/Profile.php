<?php

namespace App\Features\Profile\Livewire;

use App\Features\Profile\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Profil')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $username = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $photo = null;

    public ?string $message = null;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->username = auth()->user()->username;
    }

    public function updateProfile(ProfileService $service): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:30', Rule::unique('users', 'username')->ignore($user->id)],
        ]);

        $service->updateProfile($user, $data['name'], $data['username']);
        $this->message = 'Profil berhasil diperbarui.';
    }

    public function updatePassword(ProfileService $service): void
    {
        $data = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $service->updatePassword(auth()->user(), $data['password']);
        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->message = 'Password berhasil diubah.';
    }

    public function updatePhoto(ProfileService $service): void
    {
        $data = $this->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $service->updatePhoto(auth()->user(), $data['photo']);
        $this->reset('photo');
        $this->message = 'Foto profil berhasil diperbarui.';
    }

    public function render(): View
    {
        return view('livewire.profile.profile', ['user' => auth()->user()]);
    }
}
