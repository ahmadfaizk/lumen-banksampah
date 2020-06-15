<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function showCustomersUnconfirmed() {
        $data = DB::table('users')
            ->where('role', 'customer')
            ->whereNull('api_token')
            ->get();
        foreach ($data as $customer) {
            $customer->password = Crypt::decrypt($customer->password);
            $customer->date = Carbon::parse($customer->created_at)->format('H:i, d M yy');
        }
        return response()->json([
            'error' => false,
            'message' => 'Succes get Customers Unconfirmed',
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
        $newWithdraw = $customer->withdraw + $request->amount;
        DB::table('customers')
            ->where('id_user', $request->id_user)
            ->update([
                'balance' => $newBalance,
                'withdraw' => $newWithdraw
            ]);
        
        return response()->json([
            'error' => false,
            'message' => 'Deposit Success!',
            'data' => $transaction
        ]);
    }

    public function showComplains() {
        $data = DB::table('complaints')
            ->join('users', 'users.id', '=', 'complaints.id_customer')
            ->select('complaints.*', 'users.name as user_name')
            ->get();

        return response()->json([
            'error' => false,
            'message' => 'Success get Complaint Customer!',
            'data' => $data
        ]);
    }

    public function showCustomers() {
        $customers = DB::table('users as u')
            ->join('customers as c', 'c.id_user', '=', 'u.id')
            ->select('u.id', 'u.name', 'u.phone_number', 'u.address', 'u.password', 'c.balance', 'c.withdraw')
            ->whereNotNull('u.api_token')
            ->get();
        foreach ($customers as $customer) {
            $customer->password = Crypt::decrypt($customer->password);
        }
        return response()->json([
            'error' => false,
            'message' => 'Succes get Customers',
            'data' => $customers
        ]);
    }

    public function searchCustomers(Request $request) {
        $user = DB::table('users as u')
        ->join('customers as c', 'c.id_user', '=', 'u.id')
        ->select('u.id', 'u.name', 'u.phone_number', 'u.address', 'c.balance', 'c.withdraw')
            ->where('u.name', $request->name)
            ->get();
        return response()->json([
            'error' => false,
            'message' => 'Succes get Customers',
            'query' => $request->name,
            'data' => $user
        ]);
    }

    public function showHistory($id) {
        $customer = DB::table('customers')->where('id_user', $id)->first();
        if($customer == null) {
            return response()->json([
                'error' => true,
                'message' => 'Customer Not Found!',
                'data' => null
            ]);
        }

        $data = Transaction::where('id_user', $id)->get();
        foreach ($data as $d) {
            $d->date = Carbon::parse($d->created_at)->format('H:i, d M yy');
        }
        return response()->json([
            'error' => false,
            'message' => 'Succes get History Transactions',
            'data' => $data
        ]);
    }
}
