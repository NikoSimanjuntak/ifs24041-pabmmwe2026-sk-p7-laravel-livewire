<div class="row g-4">
    <div class="col-12">
        <h3>Profil</h3>
        @if($message)<div class="alert alert-success">{{ $message }}</div>@endif
    </div>

    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h5>Foto Profil</h5>
            @if($photo)
                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau" class="img-thumbnail mb-2" width="120">
            @elseif($user->photoUrl())
                <img src="{{ $user->photoUrl() }}" alt="Foto profil" class="img-thumbnail mb-2" width="120">
            @endif
            <form wire:submit="updatePhoto">
                <input type="file" class="form-control mb-2" wire:model="photo" accept="image/*" id="profile-photo">
                @error('photo') <div class="text-danger small">{{ $message }}</div> @enderror
                <button type="submit" class="btn btn-primary btn-sm mt-2">Simpan Foto</button>
            </form>
        </div></div>
    </div>

    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h5>Data Diri</h5>
            <form wire:submit="updateProfile">
                <div class="mb-2">
                    <label class="form-label" for="profile-name">Nama</label>
                    <input type="text" id="profile-name" class="form-control" wire:model="name">
                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="profile-username">Username</label>
                    <input type="text" id="profile-username" class="form-control" wire:model="username">
                    @error('username') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm mt-2">Simpan</button>
            </form>
        </div></div>
    </div>

    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h5>Ubah Password</h5>
            <form wire:submit="updatePassword">
                <div class="mb-2">
                    <label class="form-label" for="profile-current-password">Password Saat Ini</label>
                    <input type="password" id="profile-current-password" class="form-control" wire:model="current_password">
                    @error('current_password') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="profile-password">Password Baru</label>
                    <input type="password" id="profile-password" class="form-control" wire:model="password">
                    @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="profile-password-confirmation">Konfirmasi</label>
                    <input type="password" id="profile-password-confirmation" class="form-control" wire:model="password_confirmation">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mt-2">Ubah Password</button>
            </form>
        </div></div>
    </div>
</div>
