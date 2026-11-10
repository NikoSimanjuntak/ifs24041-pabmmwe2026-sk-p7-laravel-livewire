<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\RegisterUserAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Register')]
class Register extends Component
{
    public string $name = '';

    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(RegisterUserAction $action)
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:30', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $action->execute($data);

        return $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
