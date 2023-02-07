<?php

namespace Acelle\Vnpay\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Acelle\Model\Invoice;
use Acelle\Vnpay\Vnpay;
use Acelle\Library\Facades\Billing;
use Acelle\Cashier\Library\TransactionVerificationResult;

class VnpayController extends BaseController
{
    public function checkout(Request $request, $invoice_uid)
    {        
        $invoice = Invoice::findByUid($invoice_uid);
        $vnpay = Vnpay::initialize($invoice);

        $uri = 'https://mtf.vnpay.vn/paygate/vpcpay.op?';
        $params = [];

        $params[] = 'vpc_AccessCode='.env('ONEPAY_ACCESS_CODE');
        $params[] = 'vpc_Amount='.$invoice->total().'00';
        $params[] = 'vpc_Command=pay';
        $params[] = 'vpc_Currency=VND';
        $params[] = 'vpc_Locale=vn';
        $params[] = 'vpc_Merchant='.env('ONEPAY_MERCHANT_ID');
        $params[] = 'vpc_MerchTxnRef='.$invoice->uid.'11';
        $params[] = 'vpc_OrderInfo='.$invoice->uid;
        $params[] = 'vpc_ReturnURL='.action('\Acelle\Vnpay\Controllers\VnpayController@checkoutCheck', $invoice->uid);
        $params[] = 'vpc_TicketNo='.$request->ip();
        $params[] = 'vpc_Version=2';

        // generate hash
        var_dump(implode('&', $params));
        $hash = strtoupper(hash_hmac('sha256', implode('&', $params), env('ONEPAY_HASH_KEY')));

        $params[] = 'AgainLink='.url()->current();
        $params[] = 'Title=ABC';
        $params[] = 'vpc_SecureHash='.$hash;

        return $uri . implode('&', $params);
    }
}
