<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeightSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeightSettingsController extends Controller
{
    public function __construct(
        private WeightSettings $weightSettings
    )
    {}

    public function changeExtraChargeOnWeightStatus(Request $request)
    {
        $branchId = (integer) $request['branch_id'];
        $status = (int) $request['status'];

        $this->weightSettings->updateOrCreate(['key' => 'extra_charge_on_weight', 'branch_id' => $branchId], [
            'value' => $status
        ]);

        return redirect()->back();
    }

    public function storeWeightCharge(Request $request)
    {
        $request->validate([
            'branch_id' => 'required',
            'weight_charge_type' => 'required|in:unit,range',

            'count_charge_from' => 'required_if:weight_charge_type,unit|nullable|numeric|min:0|max:99999999',
            'count_charge_from_operation' => 'required_if:weight_charge_type,unit',
            'additional_charge_per_unit' => 'required_if:weight_charge_type,unit|nullable|numeric|min:0|max:99999999',

            'min_weight.*' => 'required_if:weight_charge_type,range|nullable|numeric|min:0|max:99999999',
            'max_weight.*' => 'required_if:weight_charge_type,range|nullable|numeric|min:0|max:99999999',
            'delivery_charge.*' => 'required_if:weight_charge_type,range|nullable|numeric|min:0|max:99999999',
        ]);

        $branchId = (int) $request->input('branch_id');

        $this->weightSettings->updateOrCreate(
            ['key' => 'weight_charge_type', 'branch_id' => $branchId],
            [
                'value' => $request->input('weight_charge_type'),
            ]
        );

        if ($request->input('weight_charge_type') == 'unit') {
            $this->weightSettings->updateOrCreate(
                ['key' => 'count_charge_from', 'branch_id' => $branchId],
                [
                    'value' => $request->input('count_charge_from'),
                    'type' => 'unit'
                ]
            );

            $this->weightSettings->updateOrCreate(
                ['key' => 'count_charge_from_operation', 'branch_id' => $branchId],
                [
                    'value' => $request->input('count_charge_from_operation'),
                    'type' => 'unit'
                ]
            );

            $this->weightSettings->updateOrCreate(
                ['key' => 'additional_charge_per_unit', 'branch_id' => $branchId],
                [
                    'value' => $request->input('additional_charge_per_unit'),
                    'type' => 'unit'
                ]
            );
        }

        if ($request->input('weight_charge_type') == 'range') {

            $minWeights = $request->input('min_weight');
            $maxWeights = $request->input('max_weight');

            foreach ($minWeights as $index => $minWeight) {
                $maxWeight = $maxWeights[$index];

                if ($maxWeight <= $minWeight) {
                    return redirect()->back()->withErrors([
                        "max_weight_{$index}" => "The max weight must be greater than the min weight"
                    ])->withInput();
                }
            }

            $weightRanges = [];

            $minWeights = $request->input('min_weight');
            $minOperations = $request->input('min_operation');
            $maxWeights = $request->input('max_weight');
            $maxOperations = $request->input('max_operation');
            $deliveryCharges = $request->input('delivery_charge');

            foreach ($minWeights as $index => $minWeight) {
                $weightRanges[] = [
                    'min_weight' => $minWeight,
                    'min_operation' => $minOperations[$index],
                    'max_weight' => $maxWeights[$index],
                    'max_operation' => $maxOperations[$index],
                    'delivery_charge' => $deliveryCharges[$index],
                ];
            }

            $this->weightSettings->updateOrCreate(
                ['key' => 'weight_range', 'branch_id' => $branchId],
                [
                    'value' => json_encode($weightRanges),
                    'type' => 'range'
                ]
            );
        }

        return redirect()->back();
    }
}
