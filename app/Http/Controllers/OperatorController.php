<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\User;
use App\Transaction;

class OperatorController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('operator');
    }

    public function index() {
        return response()->json([
            'error' => false,
            'message' => 'Succes get Operator Data',
            'data' => Auth::user()
        ]);
    }

    public function showCustomersNotConfirmed() {
        $data = DB::table('users')
            ->where('role', 'customer')
            ->whereNull('api_token')
            ->get();
        foreach ($data as $customer) {
            $customer->password = Crypt::decrypt($customer->password);
        }
        return response()->json([
            'error' => false,
            'message' => 'Succes get Customer Not Confirmed',
            'data' => $data
        ]);
    }

    public function deposit(Request $request) {
        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer',
            'amount' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Deposit Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }

        $customer = DB::table('customers')->where('id_user', $request->id_user)->first();
        if($customer == null) {
            return response()->json([
                'error' => true,
                'message' => 'Customer Not Found!',
                'data' => null
            ]);
        }

        $transaction = Transaction::create([
            'id_user' => $request->id_user,
            'amount' => $request->amount,
            'type' => 'deposit'
        ]);

        $newBalance = $customer->balance + $request->amount;

        DB::table('customers')
            ->where('id_user', $request->id_user)
            ->update(['balance' => $newBalance]);
        
        return response()->json([
            'error' => false,
            'message' => 'Deposit Success!',
            'data' => $transaction
        ]);
    }

    public function withdraw(Request $request) {
        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer',
            'amount' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Deposit Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }

        $customer = DB::table('customers')->where('id_user', $request->id_user)->first();
        if($customer == null) {
            return response()->json([
                'error' => true,
                'message' => 'Customer Not Found!',
                'data' => null
            ]);
        }

        if ($request->amount > $customer->balance) {
            return response()->json([
                'error' => true,
                'message' => 'Amount is higher than customer balance',
                'data' => null
            ]);
        }

        $transaction = Transaction::create([
            'id_user' => $request->id_user,
            'amount' => $request->amount,
            'type' => 'withdraw'
        ]);

        $newBalance = $customer->balance - $request->amount;
        DB::table('customers')
            ->where('id_user', $request->id_user)
            ->update(['balance' => $newBalance]);
        
        return response()->json([
            'error' => false,
            'message' => 'Deposit Success!',
            'data' => $transaction
        ]);
    }
}
