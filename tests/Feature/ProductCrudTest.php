<?php

use App\Models\Core\ProductModel;
use App\Models\Core\SterilizationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product certification fields can be created and updated', function () {
    $this->post(route('products.store'), [
        'name' => 'Ground Coriander',
        'code' => 'PROD-CORIANDER',
        'std_price' => 125000,
        'botanical_name' => 'Coriandrum sativum',
        'fda' => 'FDA-123',
        'fssc' => 'FSSC-22000',
        'kosher' => 'Kosher Certified',
        'halal' => 'Halal Certified',
    ])->assertSessionHasNoErrors();

    $product = ProductModel::query()
        ->where('code', 'PROD-CORIANDER')
        ->firstOrFail();

    expect($product->botanical_name)->toBe('Coriandrum sativum')
        ->and($product->fda)->toBe('FDA-123')
        ->and($product->fssc)->toBe('FSSC-22000')
        ->and($product->kosher)->toBe('Kosher Certified')
        ->and($product->halal)->toBe('Halal Certified');

    $this->put(route('products.update', $product), [
        'name' => 'Ground Coriander',
        'code' => 'PROD-CORIANDER',
        'std_price' => 125000,
        'botanical_name' => 'Coriandrum sativum Linn.',
        'fda' => 'FDA-456',
        'fssc' => 'FSSC-22000 V6',
        'kosher' => 'Kosher Approved',
        'halal' => 'Halal Approved',
    ])->assertSessionHasNoErrors();

    $product->refresh();

    expect($product->botanical_name)->toBe('Coriandrum sativum Linn.')
        ->and($product->fda)->toBe('FDA-456')
        ->and($product->fssc)->toBe('FSSC-22000 V6')
        ->and($product->kosher)->toBe('Kosher Approved')
        ->and($product->halal)->toBe('Halal Approved');
});

test('product sterilization is persisted when created', function () {
    $sterilization = SterilizationModel::create([
        'name' => 'Steam',
    ]);

    $this->post(route('products.store'), [
        'name' => 'Sterilized Product',
        'code' => 'PROD-STERILIZED',
        'std_price' => 125000,
        'sterilization_id' => $sterilization->id,
    ])->assertSessionHasNoErrors();

    $product = ProductModel::query()
        ->where('code', 'PROD-STERILIZED')
        ->firstOrFail();

    expect($product->sterilization_id)->toBe($sterilization->id);
});

test('product sterilization is persisted when updated', function () {
    $sterilization = SterilizationModel::create([
        'name' => 'Steam',
    ]);

    $product = ProductModel::create([
        'name' => 'Product to Update',
        'code' => 'PROD-UPDATE-STERILIZATION',
        'std_price' => 125000,
    ]);

    $this->put(route('products.update', $product), [
        'name' => 'Product to Update',
        'code' => 'PROD-UPDATE-STERILIZATION',
        'std_price' => 125000,
        'sterilization_id' => $sterilization->id,
    ])->assertSessionHasNoErrors();

    expect($product->refresh()->sterilization_id)->toBe($sterilization->id);
});

test('product certification fields cannot exceed their column lengths', function () {
    $this->post(route('products.store'), [
        'name' => 'Invalid Product',
        'code' => 'PROD-INVALID',
        'std_price' => 125000,
        'botanical_name' => str_repeat('B', 101),
        'fda' => str_repeat('F', 51),
        'fssc' => str_repeat('S', 51),
        'kosher' => str_repeat('K', 51),
        'halal' => str_repeat('H', 51),
    ])->assertSessionHasErrors([
        'botanical_name',
        'fda',
        'fssc',
        'kosher',
        'halal',
    ]);

    expect(ProductModel::query()->where('code', 'PROD-INVALID')->exists())->toBeFalse();
});
