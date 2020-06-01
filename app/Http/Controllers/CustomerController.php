<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Auth;
use App\Transaction;

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
        $user = DB::table('users')
            ->join('customers', 'customers.id_user', '=', 'users.id')
            ->select('users.*', 'customers.*')
            ->where('users.id', $userId)
            ->first();
        return response()->json([
            'error' => false,
            'message' => 'Succes get User Data',
            'data' => $user
        ]);
    }

    public function history() {
        $data = Transaction::where('id_user', Auth()->user()->id)->get();
        return response()->json([
            'error' => false,
            'message' => 'Succes get History Transactions',
            'data' => $data
        ]);
    }
}
