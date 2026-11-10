<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function updateProfile(User $user, string $name, string $username): User
    {
        $user->update(['name' => $name, 'username' => $username]);

        return $user;
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    public function updatePhoto(User $user, UploadedFile $photo): User
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->update(['profile_photo_path' => $photo->store('profile-photos', 'public')]);

        return $user;
    }
}
