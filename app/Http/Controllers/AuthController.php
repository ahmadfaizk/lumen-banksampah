<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use Validator;

class AuthController extends Controller
{
    public function registerCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users|max:15|min:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Register Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'role' => 'customer',
            'phone_number' => $request->phone_number,
            'password' => Crypt::encrypt(rand(100000, 999999))
        ]);

        $customer = DB::table('customers')->insert([
            'id_user' => $user->id
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Register Success!',
            'data' => null
        ]);
    }

    public function registerOperator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users|max:15|min:11',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Register Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }

        $apiToken = base64_encode(Str::random(40) . time());

        $user = User::create([
            'name' => $request->name,
            'role' => 'operator',
            'phone_number' => $request->phone_number,
            'password' => Crypt::encrypt($request->password),
            'api_token' => $apiToken
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Register Success!',
            'data' => [
                'token' => $apiToken,
                'type' => 'Bearer'
            ],
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15|min:11',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Login Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }

        $user = User::where('phone_number', $request->phone_number)->first();
        if ($user != null) {
            if ($request->password == Crypt::decrypt($user->password)) {
                $apiToken = base64_encode(Str::random(40) . time());
                $user->update([
                    'api_token' => $apiToken
                ]);
                return response()->json([
                    'error' => false,
                    'message' => 'Login Succes!',
                    'data' => [
                        'token' => $apiToken,
                        'type' => 'Bearer'
                    ],
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Login Failed!',
                    'errors_detail' => [
                        'Password wrong'
                    ],
                    'data' => null
                ]);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Login Failed!',
                'errors_detail' => [
                    'Phone Number Not Found!'
                ],
                'data' => null
            ]);
        }
    }
}
