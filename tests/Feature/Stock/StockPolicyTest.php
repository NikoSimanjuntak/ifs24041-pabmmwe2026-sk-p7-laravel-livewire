<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_rules(): void
    {
        $policy = new StockPolicy;
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->for($owner)->create();

        $this->assertTrue($policy->viewAny($owner));

        $this->assertTrue($policy->view($owner, $stock));
        $this->assertTrue($policy->view($admin, $stock));
        $this->assertFalse($policy->view($other, $stock));

        $this->assertTrue($policy->create($owner));
        $this->assertFalse($policy->create($admin));

        $this->assertTrue($policy->update($owner, $stock));
        $this->assertFalse($policy->update($other, $stock));
        $this->assertFalse($policy->update($admin, $stock));

        $this->assertTrue($policy->delete($owner, $stock));
        $this->assertFalse($policy->delete($admin, $stock));
    }
}
