<?php

namespace App\Services\CONFIGURATION;

use App\Models\User;
use App\Models\CONFIGURATION\UserPaymentMethod;
use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserPaymentMethodService
{
    /**
     * Get all users with their payment accesses, transformed for frontend.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllUsersWithPaymentAccess()
    {
       
        $users = UserPaymentMethod::with('user')
            ->get();
        return $users;
        }
    

    /**
     * Get a single user with their payment accesses, transformed for frontend.
     *
     * @param \App\Models\User $user
     * @return array
     */
    public function getUserWithPaymentAccess()
    {
        $users = UserPaymentMethod::with('user')
            ->where('user_id', $user->id)
            ->get();
        return $users;
    }

    /**
     * Create a new user and assign their payment methods.
     *
     * @param array $userData
     * @param array $allowedMethodsData
     * @return \App\Models\User
     * @throws \Exception
     */
public function assignPaymentMethodsToUsersBulk(array $paymentMethodKeys, array $userIds, string $status): int
{
    $assignedCount = 0;

    DB::beginTransaction();

    try {
        foreach ($userIds as $userId) {
            // Check if record exists for this user
            $userPaymentRecord = UserPaymentMethod::where('user_id', $userId)->first();
            
            if ($userPaymentRecord) {
                // Update existing record - merge payment methods
                $existingMethods = $userPaymentRecord->payment_method_key ?? [];
                $allMethods = array_unique(array_merge($existingMethods, $paymentMethodKeys));
                
                $userPaymentRecord->update([
                    'payment_method_key' => $allMethods,
                    'status' => $status,
                ]);
            } else {
                // Create new record
                UserPaymentMethod::create([
                    'user_id' => $userId,
                    'payment_method_key' => $paymentMethodKeys,
                    'status' => $status,
                ]);
            }
            
            $assignedCount++;
        }

        DB::commit();
        return $assignedCount;

    } catch (Exception $e) {
        DB::rollBack();
        \Log::error("Failed to assign multiple payment methods to users in bulk: " . $e->getMessage(), [
            'payment_method_keys' => $paymentMethodKeys,
            'userIds' => $userIds,
            'status' => $status,
            'exception' => $e
        ]);
        throw $e;
    }
}


    /**
     * Update an existing user and their payment methods.
     *
     * @param \App\Models\User $user
     * @param array $userData
     * @param array $allowedMethodsData
     * @return \App\Models\User
     * @throws \Exception
     */
public function updateUserPaymentMethods(User $user, array $formRequest): Collection
{
    DB::beginTransaction();
    try {
        // Delete existing payment methods for this user
        UserPaymentMethod::where('user_id', $user->id)->delete();
        
        $userPaymentMethods = collect();
        
        // Add new payment methods with the specified status
        if (isset($formRequest['allowedMethods']) && !empty($formRequest['allowedMethods'])) {
            foreach ($formRequest['allowedMethods'] as $paymentMethodKey) {
                $userPaymentMethod = UserPaymentMethod::create([
                    'user_id' => $user->id,
                    'payment_method_key' => $paymentMethodKey,
                    'status' => $formRequest['status'] ?? 'active'
                ]);
                
                $userPaymentMethods->push($userPaymentMethod);
            }
        }
        
        DB::commit();
        
        // Load relationships if needed
        $userPaymentMethods->load(['user', 'paymentMethod']); // Adjust relationship names as needed
        
        return $userPaymentMethods;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}



    /**
     * Delete a user.
     *
     * @param \App\Models\User $user
     * @return bool|null
     * @throws \Exception
     */
    public function deleteUser(User $user): ?bool
    {
        DB::beginTransaction();
        try {
            $result = $user->delete(); // onDelete('cascade') handles pivot records
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the list of available payment methods from the Enum.
     *
     * @return array
     */
    public function getAvailablePaymentMethods(): array
    {
        return PaymentMethodEnum::toArrayForDropdown();
    }

    /**
     * Helper method to sync user payment methods in the pivot table.
     *
     * @param \App\Models\User $user
     * @param array $newPaymentMethods [{key: 'prepayment', status: 'active'}, ...]
     */
    protected function syncPaymentMethods(User $user, array $newPaymentMethods)
    {
        $existingAccesses = $user->paymentAccesses->keyBy('payment_method_key');
        $methodsToKeep = [];

        foreach ($newPaymentMethods as $methodData) {
            $key = $methodData['key'];
            $status = $methodData['status'];

            if ($existingAccesses->has($key)) {
                $access = $existingAccesses->get($key);
                if ($access->status !== $status) {
                    $access->status = $status;
                    $access->save();
                }
                $methodsToKeep[] = $key;
            } else {
                UserPaymentMethod::create([
                    'user_id' => $user->id,
                    'payment_method_key' => $key,
                    'status' => $status,
                ]);
                $methodsToKeep[] = $key;
            }
        }

        $user->paymentAccesses->each(function($access) use ($methodsToKeep) {
            if (!in_array($access->payment_method_key->value, $methodsToKeep)) {
                $access->delete();
            }
        });
    }

    /**
     * Helper method to transform a User model for frontend response.
     *
     * @param \App\Models\User $user
     * @return array
     */
  
}
