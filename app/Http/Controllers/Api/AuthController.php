<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\Wallet;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\OtpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $userRepository;
    protected $driverRepository;
    protected $otpService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        DriverRepositoryInterface $driverRepository,
        OtpService $otpService
    ) {
        $this->userRepository = $userRepository;
        $this->driverRepository = $driverRepository;
        $this->otpService = $otpService;
    }

    public function sendOtp(SendOtpRequest $request)
    {
        $phoneNumber = $request->phone_number;
       if ($this->otpService->isphoneBlocked($phoneNumber)){
        return response()->json([
             'success' => false,
             'message' => 'Too many failed attempts. Please try again later.',
        ], 429);
       }
       $code = $this->otpService->generateOtp($phoneNumber);
       Log::info('OTP sent to ' . $phoneNumber . ': ' . $code);
       return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
            'data' => [
                'remaining_attempts' => $this->otpService->getRemainingAttempts($phoneNumber),]
        ], 200);
    }

    public function verifyOtp(VerifyOtpRequest $request){
        $phoneNumber = $request->phone_number;
        $code = $request->code;
        if(!$this->otpService->verifyOtp($phoneNumber, $code)){
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.',
                'data' => [
                    'remaining_attempts' => $this->otpService->getRemainingAttempts($phoneNumber),
                ]
            ], 422);
        }
        $user = $this->userRepository->findOrCreateByPhone($phoneNumber,['name'=>'User']);
        $token = $user->createToken('auth_token')->plainTextToken;
        $role = $user->is_driver ? 'driver' : 'rider';

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'data' => [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user_data' =>$user->only(['id', 'name', 'email', 'phone_number', 'avatar', 'is_driver']),
                'role' => $role,
                'is_registered' => $user->name !== 'User',
            ]
        ], 200);

    }

    public function register(RegisterRequest $request)
    {
        $user = $request->user();
        $this->userRepository->update($user, $request->only(['name', 'email', 'avatar']));

        if($request->is_driver){
            
            $this->driverRepository->create([
                'user_id' => $user->id,
                'car_type_id' => $request->car_type_id,
                'license_number' => $request->license_number,
                'plate_number' => $request->plate_number,
                'status' => 'pending',
            ]);
            
        }
        
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' =>$user->fresh(),
                'role' =>$request->boolean('is_driver') ? 'driver' : 'rider',
            ]
        ], 200);
        
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
             'message' => 'Logged out.'
             ], 200);
    }

     public function me(Request $request)
    {
        $user = $request->user();
        $role = $user->driver ? 'driver' : 'rider';

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'phone_number', 'email', 'avatar', 'wallet_balance']),
                'role' => $role,
            ]
                
        ], 200);
    }

     public function updateProfile(ProfileUpdateRequest $request)
    {
        $user = $request->user();
        $this->userRepository->update($user, $request->only(['name', 'email', 'avatar']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data' => $user->fresh()->only(['id', 'name', 'phone_number', 'email', 'avatar'])
        ]);
    }
//     public function updateFcmToken(Request $request)
// {
//     $request->validate([
//         'fcm_token' => 'required|string|max:255',
//     ]);

//     $user = $request->user();
//     $this->userRepository->update($user, ['fcm_token' => $request->fcm_token]);

//     return response()->json([
//         'success' => true,
//         'message' => 'FCM token updated successfully.',
//     ]);
// }

}

