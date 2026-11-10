<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_roles_and_relations(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Stock::factory()->for($member)->create();

        $this->assertFalse($member->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertCount(1, $member->stocks);
    }

    public function test_user_photo_url(): void
    {
        Storage::fake('public');

        $this->assertNull(User::factory()->create()->photoUrl());
        $this->assertStringContainsString('profile-photos/a.jpg', User::factory()->create(['profile_photo_path' => 'profile-photos/a.jpg'])->photoUrl());
    }

    public function test_stock_cover_url_and_owner(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create();
        $withCover = Stock::factory()->create(['cover_image_path' => 'stock-covers/a.jpg']);

        $this->assertNull($stock->coverUrl());
        $this->assertStringContainsString('stock-covers/a.jpg', $withCover->coverUrl());
        $this->assertInstanceOf(User::class, $stock->user);
    }

    public function test_deleting_user_cascades_to_stocks(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->count(2)->create();

        $user->delete();

        $this->assertDatabaseCount('stocks', 0);
    }
}
