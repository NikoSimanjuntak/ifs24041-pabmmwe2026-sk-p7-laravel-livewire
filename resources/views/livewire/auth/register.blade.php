<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3">Register</h4>
                <form wire:submit="register">
                    <div class="mb-3">
                        <label for="register-name" class="form-label">Nama</label>
                        <input type="text" id="register-name" class="form-control" wire:model="name">
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="register-username" class="form-label">Username</label>
                        <input type="text" id="register-username" class="form-control" wire:model="username">
                        @error('username') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="register-password" class="form-label">Password</label>
                        <input type="password" id="register-password" class="form-control" wire:model="password">
                        @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="register-password-confirmation" class="form-label">Konfirmasi Password</label>
                        <input type="password" id="register-password-confirmation" class="form-control" wire:model="password_confirmation">
                    </div>
                    <button type="submit" id="register-submit" class="btn btn-primary w-100">Daftar</button>
                </form>
                <p class="mt-3 mb-0 text-center">Sudah punya akun? <a href="{{ route('login') }}">Login</a></p>
            </div>
        </div>
    </div>
</div>
