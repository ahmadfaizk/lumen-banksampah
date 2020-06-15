<?php

namespace App\Http\Controllers;

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
}
