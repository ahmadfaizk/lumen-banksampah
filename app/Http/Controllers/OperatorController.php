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
            $customer->date = Carbon::parse($customer->updated_at)->format('H:i, d M yy');
            $customer->forgot_password = $customer->created_at != $customer->updated_at;
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
                'message' => 'Saldo Customer Tidak Mencukupi',
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
            ->select('complaints.id', 'complaints.text','complaints.created_at', 'users.name', 'users.phone_number', 'users.address')
            ->latest()
            ->get();

        foreach ($data as $d) {
            $d->date = Carbon::parse($d->created_at)->format('H:i, d M yy');
        }

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

        $data = Transaction::where('id_user', $id)->latest()->get();
        foreach ($data as $d) {
            $d->date = Carbon::parse($d->created_at)->format('H:i, d M yy');
        }
        return response()->json([
            'error' => false,
            'message' => 'Succes get History Transactions',
            'data' => $data
        ]);
    }

    public function showTransaction($id) {
        $transaction = Transaction::find($id);
        if ($transaction == null) {
            return response()->json([
                'error' => true,
                'message' => 'Transaction not found',
                'data' => null
            ]);
        }
        return response()->json([
            'error' => false,
            'message' => 'Succes Get Transactions',
            'data' => $transaction
        ]);
    }

    public function deleteTransaction($id) {
        $transaction = Transaction::find($id);
        if ($transaction == null) {
            return response()->json([
                'error' => true,
                'message' => 'Transaction not found',
                'data' => null
            ]);
        }

        $customer = DB::table('customers')->where('id_user', $transaction->id_user)->first();

        if ($transaction->type == 'deposit') {
            if ($customer->balance < $transaction->amount) {
                return response()->json([
                    'error' => true,
                    'message' => 'Saldo Customer Tidak Mencukupi',
                    'data' => null
                ]);
            }
            $newBalance = $customer->balance - $transaction->amount;
            DB::table('customers')
                ->where('id_user', $transaction->id_user)
                ->update(['balance' => $newBalance]);
        } elseif ($transaction->type == 'withdraw') {
            $newBalance = $customer->balance + $transaction->amount;
            $newWithdraw = $customer->withdraw - $transaction->amount;
            DB::table('customers')
                ->where('id_user', $transaction->id_user)
                ->update(['balance' => $newBalance, 'withdraw' => $newWithdraw]);
        } else {
            abort(404);
        }

        $transaction->delete();
        return response()->json([
            'error' => false,
            'message' => 'Succes Delete Transactions',
            'data' => $transaction
        ]);
    }

    public function editTransaction($id, Request $request) {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Edit Transaction Failed!',
                'errors_detail' => $validator->errors()->all(),
                'data' => null
            ]);
        }
        $transaction = Transaction::find($id);
        if ($transaction == null) {
            return response()->json([
                'error' => true,
                'message' => 'Transaction not found',
                'data' => null
            ]);
        }

        $customer = DB::table('customers')->where('id_user', $transaction->id_user)->first();

        if ($transaction->type == 'deposit') {
            if ($customer->balance < $transaction->amount - $request->amount) {
                return response()->json([
                    'error' => true,
                    'message' => 'Saldo Customer Tidak Mencukupi',
                    'data' => null
                ]);
            }
            $newBalance = $customer->balance - $transaction->amount + $request->amount;
            DB::table('customers')
                ->where('id_user', $transaction->id_user)
                ->update(['balance' => $newBalance]);
        } elseif ($transaction->type == 'withdraw') {
            if (($customer->balance + $transaction->amount) < $request->amount) {
                return response()->json([
                    'error' => true,
                    'message' => 'Saldo Customer Tidak Mencukupi',
                    'data' => null
                ]);
            }
            $newBalance = $customer->balance + $transaction->amount - $request->amount;
            $newWithdraw = $customer->withdraw - $transaction->amount + $request->amount;
            DB::table('customers')
                ->where('id_user', $transaction->id_user)
                ->update(['balance' => $newBalance, 'withdraw' => $newWithdraw]);
        } else {
            abort(404);
        }
        $transaction->amount = $request->amount;
        $transaction->save();
        return response()->json([
            'error' => false,
            'message' => 'Succes Edit Transactions',
            'data' => $transaction
        ]);
    }
}
