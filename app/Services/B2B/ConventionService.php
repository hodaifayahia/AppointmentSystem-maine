<?php

namespace App\Services\B2B;

use App\Models\B2B\Convention;
use Illuminate\Support\Facades\DB;

class ConventionService
{
    public function createConvention(array $data): Convention
    {
        return DB::transaction(function () use ($data) {
            $convention = Convention::create([
                'organisme_id' => $data['organisme_id'],
                'name' => $data['name'],
                'status' => $data['status'],
            ]);

            $convention->conventionDetail()->create([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'family_auth' => $data['family_auth'],
                'max_price' => $data['max_price'],
                'min_price' => $data['min_price'],
                'discount_percentage' => $data['discount_percentage'],
            ]);

            return $convention->load('conventionDetail');
        });
    }

    public function updateConvention(Convention $convention, array $data): Convention
    {
        return DB::transaction(function () use ($convention, $data) {
            // Update the convention
            $convention->update([
                'organisme_id' => $data['organisme_id'],
                'name' => $data['name'],
                'is_general' => $data['is_general'],
                'status' => $data['status'],
            ]);

            // Update or create convention details
            $convention->conventionDetail()->updateOrCreate(
                ['convention_id' => $convention->id],
                [
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'family_auth' => $data['family_auth'],
                    'max_price' => $data['max_price'],
                    'min_price' => $data['min_price'],
                    'discount_percentage' => $data['discount_percentage'],
                ]
            );

            return $convention->load('conventionDetail');
        });
    }
}