<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Validators\UserApiValidator;
use App\Repository\WeatherRepositoryInterface;

class UserController extends Controller
{


    private $weatherRepository;

    public function __construct(WeatherRepositoryInterface $weatherRepository)
    {
        $this->weatherRepository = $weatherRepository;
    }


    public function registerUser(Request $request)
    {

        try {

            $data = $request->except(array_keys($request->query()));
            $validateRequest = UserApiValidator::register($data);

            if (!$validateRequest->fails()) {

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                //after register login
                $credentials = [
                    'email' => $data['email'],
                    'password' => $data['password'],
                ];

                $token = auth('api')->attempt($credentials);

                $user['token'] = $token;

                $this->weatherRepository->storeToday($user); // this will fetch today's weather data from via the api and store it on the database


                return response()->json([
                    'error' => true,
                    'data' => $user,
                ], 200);

            } else {
                return response()->json([
                    'error' => true,
                    'message' => $validateRequest->errors()->first(),
                ], 400);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function loginUser(Request $request)
    {

        try {

            $data = $request->except(array_keys($request->query()));
            $validateRequest = UserApiValidator::login($data);

            if (!$validateRequest->fails()) {

                $credentials = request(['email', 'password']);

                if (!$token = auth('api')->attempt($credentials)) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                $user = auth('api')->user();
                $user['token'] = $token;

                $this->weatherRepository->storeToday($user); // this will fetch today's weather data from via the api and store it on the database

                return response()->json([
                    'error' => true,
                    'data' => $user,
                ], 200);

                return $this->respondWithToken($token);

            } else {
                return response()->json([
                    'error' => true,
                    'message' => $validateRequest->errors()->first(),
                ], 400);
            }

        } catch (\Throwable $th) {
            dd($th);
            return response()->json([
                'error' => true,
                'message' => 'Internal Server Error',
            ], 500);
        }

    }

    public function getUser()
    {

        try {

            return auth('api')->user();

        } catch (\Throwable $th) {

            return response()->json([
                'error' => true,
                'message' => 'Internal Server Error',
            ], 500);
        }

    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

}
