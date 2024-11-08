<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->environment(['testing']);
        if (app()->environment('testing')) {
            $this->artisan('migrate', ['--database' => 'testing']);
        }
    }

    public function test_product_creation_for_correct_tenant()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'database_name' => ':memory:',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/products', [
                'name' => 'Sample Product',
                'description' => 'This is a sample product.',
                'price' => 29.99
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Product created successfully'
            ]);

        DB::setDefaultConnection('tenant');
        $this->assertDatabaseHas('products', [
            'name' => 'Sample Product',
            'description' => 'This is a sample product.',
            'price' => 29.99
        ]);
    }

    public function test_product_creation_fails_without_tenant_id()
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Sample Product',
            'description' => 'This is a sample product.',
            'price' => 29.99
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Tenant ID not provided'
            ]);
    }

    public function test_product_creation_with_invalid_data()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'database_name' => ':memory:',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/products', [
                'name' => '',
                'price' => 'not-a-number',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price']);
    }
}
