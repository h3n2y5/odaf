<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Layar login runtime. Mengautentikasi terhadap SEC_USER via guard 'web'
 * (provider 'odaf'). Setelah sukses, session di-regenerate dan diarahkan ke
 * halaman yang dituju atau aplikasi demo.
 */
#[Layout('layouts.auth')]
final class Login extends Component
{
    #[Validate('required|string')]
    public string $username = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['username' => $this->username, 'password' => $this->password])) {
            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        session()->regenerate();

        $this->redirectIntended(default: route('odaf.home', ['appCode' => 'ODAF_DEMO']), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
