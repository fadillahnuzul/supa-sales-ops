<?php

use App\Models\Core\CustomerModel;
use App\Models\Core\SegmentationModel;
use App\Models\Core\SterilizationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;

uses(RefreshDatabase::class);

test('customer create update and delete accept frontend field names', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General Food Processing',
    ]);
    $standardSterilization = SterilizationModel::firstOrCreate([
        'name' => 'S',
    ]);
    $superSterilization = SterilizationModel::firstOrCreate([
        'name' => 'SS',
    ]);

    $this->post(route('customers.store'), [
        'code' => 'CUS-001',
        'company' => 'PT Maju Jaya',
        'address' => 'Jl. Merdeka No. 10',
        'segmentationId' => $segmentation->id,
        'level' => 'Low',
        'division' => 'Industri',
        'sterilization_id' => $standardSterilization->id,
        'pic' => 'Budi',
        'phone' => '081234567890',
    ])->assertSessionHasNoErrors();

    $customer = CustomerModel::first();

    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('PT Maju Jaya')
        ->and($customer->code)->toBe('CUS-001')
        ->and($customer->segmentation_id)->toBe($segmentation->id)
        ->and($customer->divisi)->toBe('Industri')
        ->and($customer->sterilization_id)->toBe($standardSterilization->id);

    $this->put(route('customers.update', $customer), [
        'code' => 'CUS-002',
        'company' => 'PT Maju Jaya Baru',
        'address' => 'Jl. Baru No. 2',
        'segmentationId' => $segmentation->id,
        'level' => 'Medium',
        'division' => 'SME',
        'sterilization_id' => $superSterilization->id,
        'pic' => 'Andi',
        'phone' => '081299988877',
    ])->assertSessionHasNoErrors();

    $customer->refresh();

    expect($customer->name)->toBe('PT Maju Jaya Baru')
        ->and($customer->code)->toBe('CUS-002')
        ->and($customer->divisi)->toBe('SME')
        ->and($customer->level)->toBe('Medium')
        ->and($customer->sterilization_id)->toBe($superSterilization->id);

    $this->put(route('customers.update', $customer), [
        'code' => null,
        'company' => 'PT Maju Jaya Baru',
        'address' => 'Jl. Baru No. 2',
        'segmentationId' => $segmentation->id,
        'level' => 'Medium',
        'division' => 'SME',
        'sterilization_id' => $superSterilization->id,
        'pic' => 'Andi',
        'phone' => '081299988877',
    ])->assertSessionHasNoErrors();

    expect($customer->refresh()->code)->toBeNull();

    $this->delete(route('customers.destroy', $customer))
        ->assertSessionHasNoErrors();

    expect(CustomerModel::count())->toBe(0);
});

test('customer accepts segmentation names and resolves them to ids', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General',
    ]);
    $sterilization = SterilizationModel::firstOrCreate([
        'name' => 'S',
    ]);

    $this->post(route('customers.store'), [
        'company' => 'PT Teknik Nusantara',
        'address' => 'Jl. Cendana No. 5',
        'segmentation' => 'General',
        'level' => 'Low',
        'division' => 'Industri',
        'sterilization_id' => $sterilization->id,
        'pic' => 'Rina',
        'phone' => '081222233334',
    ])->assertSessionHasNoErrors();

    $customer = CustomerModel::first();

    expect($customer)->not->toBeNull()
        ->and($customer->segmentation_id)->not->toBeNull()
        ->and($customer->segmentation_id)->toBe($segmentation->id);
});

test('customer import reads generated excel template and saves code and sterilization', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General',
    ]);
    $sterilization = SterilizationModel::firstOrCreate([
        'name' => 'S',
    ]);

    $template = $this->get(route('customers.template'))
        ->assertOk()
        ->streamedContent();

    $this->post(route('customers.import'), [
        'file' => File::createWithContent('customer-import.xlsx', $template),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'name' => 'PT Maju Jaya',
        'code' => 'CUS-001',
        'segmentation_id' => $segmentation->id,
        'divisi' => 'Industri',
        'sterilization_id' => $sterilization->id,
    ]);
});

test('customer import accepts older templates without sterilization', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General',
    ]);
    $sterilization = SterilizationModel::firstOrCreate([
        'name' => 'S',
    ]);

    $csv = "company,address,segmentation,level,division,pic,phone\n".
        "PT Legacy Import,Jl. Lama No. 1,General,Low,Industri,Aji,081000111222\n";

    $this->post(route('customers.import'), [
        'file' => File::createWithContent('legacy-customer-import.csv', $csv),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'name' => 'PT Legacy Import',
        'code' => null,
        'segmentation_id' => $segmentation->id,
        'sterilization_id' => $sterilization->id,
    ]);
});

test('customer code cannot exceed 50 characters', function () {
    $segmentation = SegmentationModel::create([
        'name' => 'General',
    ]);
    $sterilization = SterilizationModel::firstOrCreate([
        'name' => 'S',
    ]);

    $this->post(route('customers.store'), [
        'code' => str_repeat('C', 51),
        'company' => 'PT Invalid Code',
        'address' => 'Jl. Merdeka No. 10',
        'segmentationId' => $segmentation->id,
        'level' => 'Low',
        'division' => 'Industri',
        'sterilization_id' => $sterilization->id,
        'pic' => 'Budi',
        'phone' => '081234567890',
    ])->assertSessionHasErrors('code');

    $this->assertDatabaseMissing('customers', [
        'name' => 'PT Invalid Code',
    ]);
});

test('customer rejects an unknown sterilization ID', function () {
    $this->post(route('customers.store'), [
        'company' => 'PT Invalid Sterilization',
        'level' => 'Low',
        'division' => 'Industri',
        'sterilization_id' => 999999,
    ])->assertSessionHasErrors('sterilization_id');

    $this->assertDatabaseMissing('customers', [
        'name' => 'PT Invalid Sterilization',
    ]);
});
