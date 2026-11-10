<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3">Login</h4>
                <form wire:submit="login">
                    <div class="mb-3">
                        <label for="login-username" class="form-label">Username</label>
                        <input type="text" id="login-username" class="form-control" wire:model="username" autocomplete="username">
                        @error('username') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="login-password" class="form-label">Password</label>
                        <input type="password" id="login-password" class="form-control" wire:model="password" autocomplete="current-password">
                        @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" id="login-remember" class="form-check-input" wire:model="remember">
                        <label for="login-remember" class="form-check-label">Ingat saya</label>
                    </div>
                    <button type="submit" id="login-submit" class="btn btn-primary w-100">Masuk</button>
                </form>
                <p class="mt-3 mb-0 text-center">Belum punya akun? <a href="{{ route('register') }}">Register</a></p>
            </div>
        </div>
    </div>
</div>
