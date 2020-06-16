<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Auth;
use Validator;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index() {
        return response()->json([
            'error' => false,
            'message' => 'Succes get User Data',
            'data' => Auth::user()
        ]);
    }

    public function update(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|min:11',
            'address' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Update User Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }
        $user = Auth::user();
        $user->name = $request->name;
        $user->phone_number = $request->phone_number;
        $user->address = $request->address;
        $user->save();

        return response()->json([
            'error' => false,
            'message' => 'Succes Update User Data',
            'data' => $user
        ]);
    }

    public function changePassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Password minimal 6 karakter',
                'data' => null
            ]);
        }

        $user = Auth::user();
        $user->password = Crypt::encrypt($request->password);
        $user->save();
        return response()->json([
            'error' => false,
            'message' => 'Sukses Mengganti Password',
            'data' => $user
        ]);
    }
}
