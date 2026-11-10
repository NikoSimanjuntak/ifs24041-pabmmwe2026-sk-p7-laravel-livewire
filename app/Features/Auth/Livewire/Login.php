<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\LoginUserAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Login')]
class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    public function login(LoginUserAction $action)
    {
        $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! $action->execute($this->username, $this->password, $this->remember)) {
            $this->addError('username', 'Username atau password salah.');

            return null;
        }

        return $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
