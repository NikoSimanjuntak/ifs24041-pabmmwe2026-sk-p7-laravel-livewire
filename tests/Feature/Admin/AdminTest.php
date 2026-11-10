<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['username' => 'admin']);
    }

    public function test_admin_sees_users_with_stock_summary(): void
    {
        $member = User::factory()->create(['username' => 'siti', 'name' => 'Siti']);
        Stock::factory()->for($member)->create(['quantity' => 7]);
        Stock::factory()->for($member)->create(['quantity' => 3]);

        $this->actingAs($this->admin)->get('/admin/users')->assertOk()->assertSeeLivewire(UserList::class);

        $component = Livewire::actingAs($this->admin)->test(UserList::class)
            ->assertSee('siti')
            ->assertSee('Lihat Stok');

        $row = $component->viewData('users')->firstWhere('username', 'siti');
        $this->assertSame(2, $row->stocks_count);
        $this->assertSame(10, (int) $row->stocks_sum_quantity);
    }

    public function test_user_search(): void
    {
        User::factory()->create(['username' => 'cari_ini', 'name' => 'Target']);
        User::factory()->create(['username' => 'lainnya', 'name' => 'Abaikan']);

        Livewire::actingAs($this->admin)->test(UserList::class)
            ->set('search', 'cari_ini')
            ->assertSee('Target')
            ->assertDontSee('Abaikan')
            ->set('search', 'zzz-tidak-ada')
            ->assertSee('Tidak ada user.');
    }

    public function test_admin_sees_user_stocks_read_only(): void
    {
        $member = User::factory()->create();
        Stock::factory()->for($member)->create(['name' => 'Beras', 'sku' => 'BR-1', 'price' => null, 'status' => 'available']);
        Stock::factory()->for($member)->create(['name' => 'Gula', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->create(['name' => 'Bukan Milik Member']);

        $this->actingAs($this->admin)->get('/admin/users/'.$member->id.'/stocks')->assertOk();

        Livewire::actingAs($this->admin)->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Beras')
            ->assertDontSee('Bukan Milik Member')
            ->assertDontSee('Hapus')
            ->assertDontSee('Edit')
            ->set('search', 'Gula')->assertSee('Gula')->assertDontSee('Beras')
            ->set('search', '')
            ->set('statusFilter', 'available')->assertSee('Beras')->assertDontSee('Gula')
            ->set('statusFilter', 'low_stock')->assertSee('Belum ada stok.');
    }

    public function test_admin_cannot_modify_stock_via_http(): void
    {
        $stock = Stock::factory()->create();

        $this->actingAs($this->admin)->get('/stocks/'.$stock->id)->assertForbidden();
    }
}
