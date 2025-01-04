<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Product;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up the master database (tenants table)
        Artisan::call('migrate');

        // Create a tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'database_name' => 'test_tenant_db',
        ]);

        // Create the tenant's database
        Config::set('database.connections.tenant_temp', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Run tenant migrations
        Artisan::call('migrate', [
            '--database' => 'tenant_temp',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        // Set the tenant connection to use the in-memory database
        Config::set('database.connections.tenant', Config::get('database.connections.tenant_temp'));
        \DB::setDefaultConnection('tenant');
    }

    /** @test */
    public function it_adds_product_to_correct_tenant_database()
    {
        $payload = [
            'name' => 'Test Product',
            'description' => 'A product for testing.',
            'price' => 49.99,
        ];

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Product created successfully.',
                'data' => [
                    'name' => 'Test Product',
                    'description' => 'A product for testing.',
                    'price' => 49.99,
                ],
            ]);

        // Assert the product exists in the tenant's database
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'description' => 'A product for testing.',
            'price' => 49.99,
        ], 'tenant');
    }

    /** @test */
    public function it_fails_when_tenant_id_is_missing()
    {
        $payload = [
            'name' => 'Test Product',
            'description' => 'A product without tenant ID.',
            'price' => 19.99,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Tenant ID not provided']);
    }

    /** @test */
    public function it_fails_with_invalid_tenant_id()
    {
        $payload = [
            'name' => 'Test Product',
            'description' => 'A product with invalid tenant ID.',
            'price' => 19.99,
        ];

        $response = $this->withHeader('X-Tenant-ID', 999) // Assuming this ID doesn't exist
            ->postJson('/api/products', $payload);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Invalid Tenant ID']);
    }

    /** @test */
    public function it_validates_input_data()
    {
        $payload = [
            'name' => '', // Required field
            'price' => -10, // Invalid value
        ];

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/products', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price']);
    }
}
