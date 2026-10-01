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
        'pic' => 'Budi',
        'phone' => '081234567890',
    ])->assertSessionHasNoErrors();

    $customer = CustomerModel::first();

    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('PT Maju Jaya')
        ->and($customer->segmentation_id)->toBe($segmentation->id)
        ->and($customer->divisi)->toBe('Industri');

    $this->put(route('customers.update', $customer), [
        'company' => 'PT Maju Jaya Baru',
        'address' => 'Jl. Baru No. 2',
        'segmentationId' => $segmentation->id,
        'level' => 'Medium Risk',
        'division' => 'SME',
        'pic' => 'Andi',
        'phone' => '081299988877',
    ])->assertSessionHasNoErrors();

    $customer->refresh();

    expect($customer->name)->toBe('PT Maju Jaya Baru')
        ->and($customer->divisi)->toBe('SME')
        ->and($customer->level)->toBe('Medium Risk');

    $this->delete(route('customers.destroy', $customer))
        ->assertSessionHasNoErrors();

    expect(CustomerModel::count())->toBe(0);
});
