<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;
use App\Models\Transaction;

class MidtransController extends Controller
{
    public function callback(Request $request)
    {
        // Set Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.serverkey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Buat Instance midtrans notification  
        $notification = new Notification();

        // Assign ke variable untuk memudahkan coding
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // Cari transaksi berdasarkan id
        $transaction = Transaction::findOrFails($order_id);

        // Handle notifikasi status midtrans
        if($status == 'capture')
        {
            if($type == 'credit_card')
            {
                if($fraud == 'challenge')
                {
                    $transaction->status = 'PENDING';
                }
                else
                {
                    $transaction->status = 'SUCCESS';
                }
            }
        }
        // Sudah terbayar
        elseif($status == 'settlement')
        {
            $transaction->status = 'SUCCESS';
        }
        // PENDING
        elseif($status == 'pending')
        {
            $transaction->status = 'PENDING';
        }
        elseif($status == 'deny')
        {
            $transaction->status = 'CANCELLED';
        }
        
        elseif($status == 'expire')
        {
            $transaction->status = 'CANCELLED';
        }
        elseif($status == 'cancel')
        {
            $transaction->status = 'CANCELLED';
        }

        // Simpan transaksi
        $transaction->save();

    }

    
    public function success()
    {
        return view('midtrans.success');
    }
    
    public function unfinish()
    {
        return view('midtrans.unfinish');
    }

    public function error()
    {
        return view('midtrans.error');
    }

}
