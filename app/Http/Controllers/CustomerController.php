<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Illuminate\Support\Carbon;
use App\Transaction;
use App\Complaints;

class CustomerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('customer');
    }

    public function index() {
        $userId = Auth()->user()->id;
        $user = DB::table('users as u')
            ->join('customers as c', 'c.id_user', '=', 'u.id')
            ->select('u.id', 'u.name', 'u.phone_number', 'u.address', 'c.balance', 'c.withdraw')
            ->where('u.id', $userId)
            ->first();
        return response()->json([
            'error' => false,
            'message' => 'Succes get User Data',
            'data' => $user
        ]);
    }

    public function showHistory() {
        $data = Transaction::where('id_user', Auth()->user()->id)->latest()->get();
        foreach ($data as $d) {
            $d->date = Carbon::parse($d->created_at)->format('H:i, d M yy');
        }
        return response()->json([
            'error' => false,
            'message' => 'Succes get History Transactions',
            'data' => $data
        ]);
    }

    public function complain(Request $request) {
        $validator = Validator::make($request->all(), [
            'complaint' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Complain Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }
        $userId = Auth()->user()->id;
        $data = Complaints::create([
            'id_customer' => $userId,
            'text' => $request->complaint,
        ]);
        return response()->json([
            'error' => false,
            'message' => 'Complain Success!',
            'data' => $data
        ]);
    }

    public function withdraw(Request $request) {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Penarikan Saldo Gagal!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }
        
        $amount = $request->amount;
        $userId = Auth()->user()->id;
        $customer = DB::table('customers')->where('id_user', $userId)->first();
        if ($amount > $customer->balance) {
            return response()->json([
                'error' => true,
                'message' => 'Saldo Anda Tidak Mencukupi',
                'data' => null
            ]);
        }

        $transaction = Transaction::create([
            'id_user' => $userId,
            'amount' => $request->amount,
            'type' => 'withdraw'
        ]);
        $newBalance = $customer->balance - $amount;
        $newWithdraw = $customer->withdraw + $amount;
        DB::table('customers')
            ->where('id_user', $userId)
            ->update([
                'balance' => $newBalance,
                'withdraw' => $newWithdraw
            ]);
        
        return response()->json([
            'error' => false,
            'message' => 'Penarikan Saldo Success!',
            'data' => $transaction
        ]);
    }
}
