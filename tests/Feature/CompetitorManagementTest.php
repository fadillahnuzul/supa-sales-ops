<?php

use App\Models\Core\ProductModel;
use App\Models\Sales\CompetitorModel;
use App\Models\Sales\CompetitorProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createCompetitorTestProduct(string $name): ProductModel
{
    return ProductModel::create([
        'name' => $name,
        'code' => strtoupper(str_replace(' ', '-', $name)),
        'std_price' => 10000,
    ]);
}

test('database center loads competitor data from persisted records', function () {
    $competitor = CompetitorModel::create([
        'name' => 'Database Competitor',
        'divisi' => 'Industri',
    ]);
    $product = createCompetitorTestProduct('Database Product');

    CompetitorProductModel::create([
        'competitor_id' => $competitor->getKey(),
        'product_id' => $product->getKey(),
        'price' => 23000,
        'date' => '2026-10-02',
    ]);

    $this->get(route('database-center'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DatabaseCenter')
            ->has('competitors', 1)
            ->where('competitors.0.competitor', 'Database Competitor')
            ->where('competitors.0.product', 'Database Product')
            ->where('competitors.0.price', 23000.0));
});

test('competitor price row can be edited using its own identifier', function () {
    CompetitorModel::create([
        'name' => 'Another Competitor',
        'divisi' => 'SME',
    ]);
    $competitor = CompetitorModel::create([
        'name' => 'Original Competitor',
        'divisi' => 'Industri',
    ]);
    $originalProduct = createCompetitorTestProduct('Original Product');
    $updatedProduct = createCompetitorTestProduct('Updated Product');
    $competitorProduct = CompetitorProductModel::create([
        'competitor_id' => $competitor->getKey(),
        'product_id' => $originalProduct->getKey(),
        'price' => 10000,
        'date' => '2026-09-01',
    ]);

    $this->put(route('competitors.update', $competitorProduct), [
        'name' => 'Updated Competitor',
        'divisi' => 'Low Cost',
        'note' => 'Updated competitor note',
        'product_id' => $updatedProduct->getKey(),
        'price' => 25000,
        'date' => '2026-10-02',
        'product_note' => 'Updated product note',
    ])->assertSessionHasNoErrors();

    $competitor->refresh();
    $competitorProduct->refresh();

    expect($competitor->name)->toBe('Updated Competitor')
        ->and($competitor->divisi)->toBe('Low Cost')
        ->and($competitor->note)->toBe('Updated competitor note')
        ->and($competitorProduct->product_id)->toBe($updatedProduct->getKey())
        ->and((float) $competitorProduct->price)->toBe(25000.0)
        ->and($competitorProduct->note)->toBe('Updated product note')
        ->and($competitorProduct->date->toDateString())->toBe('2026-10-02');
});

test('competitor price row can be deleted without deleting its competitor', function () {
    $competitor = CompetitorModel::create([
        'name' => 'Competitor To Keep',
        'divisi' => 'Industri',
    ]);
    $product = createCompetitorTestProduct('Product To Remove');
    $competitorProduct = CompetitorProductModel::create([
        'competitor_id' => $competitor->getKey(),
        'product_id' => $product->getKey(),
        'price' => 10000,
        'date' => '2026-10-02',
    ]);

    $this->delete(route('competitors.products.destroy', $competitorProduct))
        ->assertSessionHasNoErrors();

    $this->assertModelMissing($competitorProduct);
    $this->assertModelExists($competitor);
});

test('competitor import groups products under one competitor', function () {
    $productOne = createCompetitorTestProduct('Import Product One');
    $productTwo = createCompetitorTestProduct('Import Product Two');
    $csv = "competitor,division,competitor_note,product_code,price,date,product_note\n".
        'Bulk Competitor,Industri,Main supplier,'.$productOne->code.",12500,2026-10-01,First price\n".
        'Bulk Competitor,Industri,Main supplier,'.$productTwo->code.",15000,2026-10-02,Second price\n";

    $this->post(route('competitors.import'), [
        'file' => File::createWithContent('competitor-import.csv', $csv),
    ])->assertSessionHasNoErrors();

    $competitor = CompetitorModel::query()
        ->where('name', 'Bulk Competitor')
        ->firstOrFail();

    expect($competitor->products()->count())->toBe(2)
        ->and($competitor->products()->orderBy('product_id')->pluck('price')->all())
        ->toBe(['12500.00', '15000.00']);
});

test('competitor import rejects unknown product codes without saving partial data', function () {
    $product = createCompetitorTestProduct('Known Import Product');
    $csv = "competitor,division,competitor_note,product_code,price,date,product_note\n".
        "Bulk Competitor,Industri,,{$product->code},12500,2026-10-01,\n".
        "Bulk Competitor,Industri,,UNKNOWN-CODE,15000,2026-10-02,\n";

    $this->post(route('competitors.import'), [
        'file' => File::createWithContent('invalid-competitor-import.csv', $csv),
    ])->assertSessionHasErrors('file');

    expect(CompetitorModel::query()->count())->toBe(0)
        ->and(CompetitorProductModel::query()->count())->toBe(0);
});
