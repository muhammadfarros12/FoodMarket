<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Models\Trasansaction;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if($id)
        {
            $transaction = Transaction::with(['food', 'user'])->find($id);

            if($transaction)
            {
                return ResponseFormatter::success(
                    $transaction,
                    'Data Trasnsaksi berhasil diambil'
                );
            }
            else
            {
                return ResponseFormatter::success(
                    null,
                    'Data Trasnsaksi tidak ada',
                    404
                );
            }
        }

        $transaction = Transaction::with(['food', 'user'])
                        ->where('user_id', Auth::user()->id);

        if($food_id)
        {
            $transaction->where('food_id',$food_id);
        }
        
        if($status)
        {
            $transaction->where('status',$status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list Transaksi berhasil diambil'
        );

    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success(
                $transaction,
                'Transaksi berhasil diupdate'
        );
    }

    public function checkout(Request $request)
    {
        // validasi request
        $request->validate([
            'food_id'=>'required|exist:food,id',
            'user_id'=>'required|exist:users,id',
            'quantity'=>'required',
            'total'=> 'required',
            'status'=> 'required'
        ]);

        // pembuatan database
        $transaction = Transaction::create([
            'food_id'=> $request->food_id,
            'user_id'=> $request->user_id,
            'quantity'=> $request->quantity,
            'total'=> $request->total,
            'status'=> $request->status,
            'payment_url'=> ''
        ]);

        // Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.serverkey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // panggil transaksi yang tadi dibuat
        $transaction = Transaction::with(['food','user'])->find($transaction->id);

        // Membuat transaksi Midtrans
        $midtrans = [
            'transaction-details'=> [
                'order-id'=>$transaction->id,
                'gross_amount'=> (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name'=> $transaction->user->name,
                'email'=> $transaction->user->email,
            ],
            'enable_payments' => ['gopay', 'bank_transfer'],
            'vtweb' => []
        ];

        // Memanggil Midtrans
        try {
            // Ambil halaman payment midtrans
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

            $transaction->payment_url = $paymentUrl;
            $transaction->save();

        // Mengembalikan data ke API
        return ResponseFormatter::success(
            $transaction,
            'Transaksi berhasil'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                $e->getMessage(),
                'Transaksi gagal'
            );
        }
    }

}
