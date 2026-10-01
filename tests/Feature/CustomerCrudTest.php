<?php

use App\Models\Core\CustomerModel;
use App\Models\Core\SegmentationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customer create update and delete accept frontend field names', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General Food Processing',
    ]);

    $this->post(route('customers.store'), [
        'company' => 'PT Maju Jaya',
        'address' => 'Jl. Merdeka No. 10',
        'segmentationId' => $segmentation->id,
        'level' => 'Low',
        'division' => 'Industri',
        'sterilization' => 'S',
        'pic' => 'Budi',
        'phone' => '081234567890',
    ])->assertSessionHasNoErrors();

    $customer = CustomerModel::first();

    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('PT Maju Jaya')
        ->and($customer->segmentation_id)->toBe($segmentation->id)
        ->and($customer->divisi)->toBe('Industri')
        ->and($customer->sterilization)->toBe('S');

    $this->put(route('customers.update', $customer), [
        'company' => 'PT Maju Jaya Baru',
        'address' => 'Jl. Baru No. 2',
        'segmentationId' => $segmentation->id,
        'level' => 'Medium',
        'division' => 'SME',
        'sterilization' => 'SS',
        'pic' => 'Andi',
        'phone' => '081299988877',
    ])->assertSessionHasNoErrors();

    $customer->refresh();

    expect($customer->name)->toBe('PT Maju Jaya Baru')
        ->and($customer->divisi)->toBe('SME')
        ->and($customer->level)->toBe('Medium')
        ->and($customer->sterilization)->toBe('SS');

    $this->delete(route('customers.destroy', $customer))
        ->assertSessionHasNoErrors();

    expect(CustomerModel::count())->toBe(0);
});

test('customer accepts segmentation names and resolves them to ids', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General',
    ]);

    $this->post(route('customers.store'), [
        'company' => 'PT Teknik Nusantara',
        'address' => 'Jl. Cendana No. 5',
        'segmentation' => 'General',
        'level' => 'Low',
        'division' => 'Industri',
        'sterilization' => 'S',
        'pic' => 'Rina',
        'phone' => '081222233334',
    ])->assertSessionHasNoErrors();

    $customer = CustomerModel::first();

    expect($customer)->not->toBeNull()
        ->and($customer->segmentation_id)->not->toBeNull()
        ->and($customer->segmentation_id)->toBe($segmentation->id);
});

test('customer import reads generated excel template and saves sterilization', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General',
    ]);

    $template = $this->get(route('customers.template'))
        ->assertOk()
        ->streamedContent();

    $this->post(route('customers.import'), [
        'file' => \Illuminate\Http\Testing\File::createWithContent('customer-import.xlsx', $template),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'name' => 'PT Maju Jaya',
        'segmentation_id' => $segmentation->id,
        'divisi' => 'Industri',
        'sterilization' => 'S',
    ]);
});

test('customer import accepts older templates without sterilization', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General',
    ]);

    $csv = "company,address,segmentation,level,division,pic,phone\n".
        "PT Legacy Import,Jl. Lama No. 1,General,Low,Industri,Aji,081000111222\n";

    $this->post(route('customers.import'), [
        'file' => \Illuminate\Http\Testing\File::createWithContent('legacy-customer-import.csv', $csv),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'name' => 'PT Legacy Import',
        'segmentation_id' => $segmentation->id,
        'sterilization' => 'S',
    ]);
});
