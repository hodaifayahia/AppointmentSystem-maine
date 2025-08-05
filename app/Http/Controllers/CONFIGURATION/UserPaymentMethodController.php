<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\CONFIGURATION\UserPaymentMethodService; // Import the service
use App\Http\Resources\CONFIGURATION\UserResource;
use App\Http\Resources\CONFIGURATION\UserPaymentMethodResource; // Import the resource
use App\Http\Requests\CONFIGURATION\UserPaymentMethodRequest;

class UserPaymentMethodController extends Controller
{
    protected $userPaymentMethodService;

    /**
     * Constructor to inject the UserPaymentMethodService.
     *
     * @param UserPaymentMethodService $userPaymentMethodService
     */
    public function __construct(UserPaymentMethodService $userPaymentMethodService)
    {
        $this->userPaymentMethodService = $userPaymentMethodService;
    }

    /**
     * Display a listing of the users with their payment access.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = $this->userPaymentMethodService->getAllUsersWithPaymentAccess();
        // The service already transforms the data into the desired array structure,
        // so we return it directly as a JSON response.
        return UserPaymentMethodResource::collection($users);
    }

    /**
     * Store a newly created user and their payment access in storage.
     *
     * @param  \App\Http\Requests\CONFIGURATION\UserPaymentMethodRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function store(UserPaymentMethodRequest $request)
    // {
    //     try {
    //         $user = $this->userPaymentMethodService->assginpaymentmethodTousers(
    //             $request->validated(), // Pass validated data from the Form Request
    //         );
    //         // Use UserResource to transform the created user model for the response
    //         return UserResource::make($user)->response()->setStatusCode(201);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => 'Failed to create user and assign payment methods.', 'error' => $e->getMessage()], 500);
    //     }
    // }
    // THIS IS THE CODE FOR bulkAssign, NOT store
public function store(UserPaymentMethodRequest $request)
    {
      try {
            $paymentMethodKeys = $request->input('paymentMethodKeys'); // Get the array of keys
            $userIds = $request->input('userIds');
            $status = $request->input('status');

            $assignedCount = $this->userPaymentMethodService->assignPaymentMethodsToUsersBulk(
                $paymentMethodKeys,
                $userIds,
                $status
            );

            return response()->json([
                'message' => "Successfully assigned {$assignedCount} payment method accesses.",
                'assigned_count' => $assignedCount,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to perform bulk assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    
    }

    /**
     * Display the specified user with their payment access.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        $transformedUser = $this->userPaymentMethodService->getUserWithPaymentAccess($user);
        // The service already transforms the data into the desired array structure,
        // so we return it directly as a JSON response.
        return response()->json($transformedUser);
    }

    /**
     * Update the specified user and their payment access in storage.
     *
     * @param  \App\Http\Requests\CONFIGURATION\UserPaymentMethodRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
public function update(UserPaymentMethodRequest $request, User $user)
{
    try {
        $userPaymentMethods = $this->userPaymentMethodService->updateUserPaymentMethods(
            $user,
            $request->validated()
        );
        
        // Return collection of UserPaymentMethod resources
        return UserPaymentMethodResource::collection($userPaymentMethods);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update user payment methods.', 
            'error' => $e->getMessage()
        ], 500);
    }
}



    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        try {
            $this->userPaymentMethodService->deleteUser($user);
            return response()->json(['message' => 'User deleted successfully.'], 204); // 204 No Content
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete user.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the list of available payment methods from the Enum.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethods()
    {
    return \App\Enums\Payment\PaymentMethodEnum::toArrayForDropdown();
    }
   

}
