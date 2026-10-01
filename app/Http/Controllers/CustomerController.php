<?php

namespace App\Http\Controllers;

use App\Models\Core\CustomerModel;
use App\Models\Core\SegmentationModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = CustomerModel::query()
            ->with('segmentation:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (CustomerModel $customer) {
                return [
                    'id' => $customer->id,

                    // Mapping database -> frontend
                    'customerId' => $customer->id,
                    'company' => $customer->name,
                    'address' => $customer->address,

                    'segmentationId' =>
                        $customer->segmentation_id,

                    'segmentation' =>
                        $customer->segmentation?->name ?? '-',

                    'level' => $customer->level,

                    'division' => $customer->divisi,

                    'pic' => $customer->pic,
                    'phone' => $customer->phone,

                    'sterilization' =>
                        $customer->sterilization,
                ];
            });

        $segmentations = SegmentationModel::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'DatabaseCenter',
            [
                'customers' => $customers,
                'segmentations' => $segmentations,
            ]
        );
    }


    /**
     * Store new customer.
     */
    public function store(Request $request)
    {
        $validated = $this->validateCustomer(
            $request
        );

        CustomerModel::create($validated);

        return back()->with(
            'success',
            'Customer successfully added.'
        );
    }


    /**
     * Update customer.
     */
    public function update(
        Request $request,
        CustomerModel $customer
    ) {
        $validated = $this->validateCustomer(
            $request
        );

        $customer->update($validated);

        return back()->with(
            'success',
            'Customer successfully updated.'
        );
    }


    /**
     * Delete customer.
     */
    public function destroy(CustomerModel $customer)
    {
        $customer->delete();

        return back()->with(
            'success',
            'Customer successfully deleted.'
        );
    }


    /**
     * Customer validation.
     */
    private function validateCustomer(
        Request $request
    ): array {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'company' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'segmentation_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    SegmentationModel::class,
                    'id'
                ),
            ],
            'segmentationId' => [
                'nullable',
                'integer',
                Rule::exists(
                    SegmentationModel::class,
                    'id'
                ),
            ],
            'level' => [
                'nullable',
                Rule::in([
                    'Low',
                    'Medium',
                    'High',
                ]),
            ],
            'divisi' => [
                'nullable',
                Rule::in([
                    'Industri',
                    'SME',
                    'Low Cost',
                    'All',
                ]),
            ],
            'division' => [
                'nullable',
                Rule::in([
                    'Industri',
                    'SME',
                    'Low Cost',
                    'All',
                ]),
            ],
            'pic' => [
                'nullable',
                'string',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'sterilization' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['name'] ??= $validated['company'] ?? null;
        $validated['segmentation_id'] ??= $validated['segmentationId'] ?? null;
        $validated['divisi'] ??= $validated['division'] ?? null;

        return array_filter(
            $validated,
            fn ($value) => $value !== null
        );
    }
}