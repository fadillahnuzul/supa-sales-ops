<?php

use App\Models\Core\MaterialModel;
use App\Models\Core\ProductMaterialModel;
use App\Models\Core\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;

uses(RefreshDatabase::class);

test('product template imports multiple materials from one semicolon separated cell', function () {
    $lada = MaterialModel::create([
        'name' => 'Lada',
    ]);
    $kapulaga = MaterialModel::create([
        'name' => 'Kapulaga',
    ]);

    $template = $this->get(route('products.template'))
        ->assertOk()
        ->streamedContent();

    $this->post(route('products.import'), [
        'file' => File::createWithContent('product-import-template.xlsx', $template),
    ])->assertSessionHasNoErrors();

    $product = ProductModel::query()
        ->where('code', 'PROD-DEMO-001')
        ->firstOrFail();
    $materialIds = ProductMaterialModel::query()
        ->where('product_id', $product->getKey())
        ->pluck('material_id')
        ->all();

    expect($product->name)->toBe('Racikan Lada Kapulaga')
        ->and((float) $product->std_price)->toBe(125000.0)
        ->and($materialIds)->toEqualCanonicalizing([
            $lada->getKey(),
            $kapulaga->getKey(),
        ]);
});

test('product import links the first matching material record when names are duplicated', function () {
    $firstCardamom = MaterialModel::create([
        'name' => 'Cardamom',
    ]);
    $secondCardamom = MaterialModel::create([
        'name' => 'Cardamom',
    ]);
    $csv = "name,code,std_price,grade,materials\n".
        "Produk Cardamom,PROD-CARDAMOM,10000,,Cardamom\n";

    $this->post(route('products.import'), [
        'file' => File::createWithContent('duplicate-material-name.csv', $csv),
    ])->assertSessionHasNoErrors();

    $product = ProductModel::query()
        ->where('code', 'PROD-CARDAMOM')
        ->firstOrFail();
    $materialIds = ProductMaterialModel::query()
        ->where('product_id', $product->getKey())
        ->where('material_type', $firstCardamom->getMorphClass())
        ->pluck('material_id')
        ->all();

    expect($materialIds)->toBe([$firstCardamom->getKey()])
        ->and($materialIds)->not->toContain($secondCardamom->getKey());
});

test('product import rolls back all rows when a material name cannot be resolved', function () {
    $csv = "name,code,std_price,grade,materials\n".
        "Produk Valid,PROD-VALID,10000,,\n".
        "Produk Tidak Valid,PROD-INVALID,20000,,Material Tidak Ada\n";

    $this->post(route('products.import'), [
        'file' => File::createWithContent('product-import.csv', $csv),
    ])->assertSessionHasErrors('file');

    expect(ProductModel::query()->count())->toBe(0);
});

test('product import rolls back products with circular material references', function () {
    $csv = "name,code,std_price,grade,materials\n".
        "Produk A,PROD-A,10000,,Produk B\n".
        "Produk B,PROD-B,20000,,Produk A\n";

    $this->post(route('products.import'), [
        'file' => File::createWithContent('circular-products.csv', $csv),
    ])->assertSessionHasErrors('file');

    expect(ProductModel::query()->count())->toBe(0)
        ->and(ProductMaterialModel::query()->count())->toBe(0);
});
