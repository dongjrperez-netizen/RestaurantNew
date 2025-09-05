<?php

namespace App\Http\Controllers;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\UserSubscription;
use App\Models\Paymentinfo;
use App\Models\Subpayment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class UsersubscriptionController extends Controller
{
   public function payment(){
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->setAccessToken($provider->getAccessToken());

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "purchase_units"=>[
                [
                    "amount"=>[
                        "currency_code" => "PHP",
                        "value"=> 100.00,

                    ]
                    
                ]
            ]
        ]);
   }
}
