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

        $vnp_TmnCode = env('VNPAY_TMN_CODE', "539IQENL"); //Mã định danh merchant kết nối (Terminal Id)
        $vnp_HashSecret = env('VNPAY_HASH_SECRET', "FGUSHKBOHOLHWAQNHWXVYBPZJJBNYJUT"); //Secret key
        $vnp_Url = env('VNPAY_URL', "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html");
        $vnp_Returnurl = action('\Acelle\Vnpay\Controllers\VnpayController@checkoutCheck', $invoice->uid);

        //Config input format
        //Expire
        $startTime = date("YmdHis");
        $expire = date('YmdHis',strtotime('+5 days',strtotime($startTime)));

        $vnp_TxnRef = rand(1,10000); //Mã giao dịch thanh toán tham chiếu của merchant
        $vnp_Amount = $invoice->total(); // Số tiền thanh toán
        $vnp_Locale = 'vn'; //Ngôn ngữ chuyển hướng thanh toán
        $vnp_BankCode = ''; //Mã phương thức thanh toán
        $vnp_IpAddr = $request->ip(); //IP Khách hàng thanh toán

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount* 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh toan GD:" . $vnp_TxnRef,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate"=>$expire
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);//  
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        header('Location: ' . $vnp_Url);
        die();

        // $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        // $vnp_Returnurl = action('\Acelle\Vnpay\Controllers\VnpayController@checkoutCheck', $invoice->uid);
        // $vnp_TmnCode = "539IQENL";//Mã website tại VNPAY 
        // $vnp_HashSecret = "FGUSHKBOHOLHWAQNHWXVYBPZJJBNYJUT"; //Chuỗi bí mật

        // $vnp_TxnRef = $invoice->uid; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        // $vnp_OrderInfo = $invoice->description;
        // $vnp_OrderType = '250000';
        // $vnp_Amount = $invoice->total() * 100;
        // $vnp_Locale = 'vn';
        // $vnp_BankCode = "";
        // $vnp_IpAddr = $request->ip();
        // //Add Params of 2.0.1 Version
        // $vnp_ExpireDate = "20231229153052";
        // //Billing
        // $vnp_Bill_Mobile = $invoice->billing_phone;
        // $vnp_Bill_Email = $invoice->billing_email;
        // $fullName = trim($invoice->getBillingName());
        // if (isset($fullName) && trim($fullName) != '') {
        //     $name = explode(' ', $fullName);
        //     $vnp_Bill_FirstName = array_shift($name);
        //     $vnp_Bill_LastName = array_pop($name);
        // }
        // $vnp_Bill_Address=$invoice->billing_address;
        // $vnp_Bill_City='Ho Chi Minh';
        // $vnp_Bill_Country='VN';
        // $vnp_Bill_State='Ho Chi Minh';
        // // Invoice
        // $vnp_Inv_Phone=$invoice->billing_phone;
        // $vnp_Inv_Email=$invoice->billing_email;
        // $vnp_Inv_Customer=$invoice->getBillingName();
        // $vnp_Inv_Address=$invoice->billing_address;
        // $vnp_Inv_Company="";
        // $vnp_Inv_Taxcode="";
        // $vnp_Inv_Type="";
        // $inputData = array(
        //     "vnp_Version" => "2.1.0",
        //     "vnp_TmnCode" => $vnp_TmnCode,
        //     "vnp_Amount" => $vnp_Amount,
        //     "vnp_Command" => "pay",
        //     "vnp_CreateDate" => date('YmdHis'),
        //     "vnp_CurrCode" => "VND",
        //     "vnp_IpAddr" => $vnp_IpAddr,
        //     "vnp_Locale" => $vnp_Locale,
        //     "vnp_OrderInfo" => $vnp_OrderInfo,
        //     "vnp_OrderType" => $vnp_OrderType,
        //     "vnp_ReturnUrl" => $vnp_Returnurl,
        //     "vnp_TxnRef" => $vnp_TxnRef,
        //     "vnp_ExpireDate"=>$vnp_ExpireDate,
        //     "vnp_Bill_Mobile"=>$vnp_Bill_Mobile,
        //     "vnp_Bill_Email"=>$vnp_Bill_Email,
        //     "vnp_Bill_FirstName"=>$vnp_Bill_FirstName,
        //     "vnp_Bill_LastName"=>$vnp_Bill_LastName,
        //     "vnp_Bill_Address"=>$vnp_Bill_Address,
        //     "vnp_Bill_City"=>$vnp_Bill_City,
        //     "vnp_Bill_Country"=>$vnp_Bill_Country,
        //     "vnp_Inv_Phone"=>$vnp_Inv_Phone,
        //     "vnp_Inv_Email"=>$vnp_Inv_Email,
        //     "vnp_Inv_Customer"=>$vnp_Inv_Customer,
        //     "vnp_Inv_Address"=>$vnp_Inv_Address,
        //     "vnp_Inv_Company"=>$vnp_Inv_Company,
        //     "vnp_Inv_Taxcode"=>$vnp_Inv_Taxcode,
        //     "vnp_Inv_Type"=>$vnp_Inv_Type
        // );

        // if (isset($vnp_BankCode) && $vnp_BankCode != "") {
        //     $inputData['vnp_BankCode'] = $vnp_BankCode;
        // }
        // if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
        //     $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        // }

        // //var_dump($inputData);
        // ksort($inputData);
        // $query = "";
        // $i = 0;
        // $hashdata = "";
        // foreach ($inputData as $key => $value) {
        //     if ($i == 1) {
        //         $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        //     } else {
        //         $hashdata .= urlencode($key) . "=" . urlencode($value);
        //         $i = 1;
        //     }
        //     $query .= urlencode($key) . "=" . urlencode($value) . '&';
        // }

        // $vnp_Url = $vnp_Url . "?" . $query;
        // if (isset($vnp_HashSecret)) {
        //     $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);//  
        //     $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        // }
        // $returnData = array('code' => '00'
        //     , 'message' => 'success'
        //     , 'data' => $vnp_Url);

        // if (true) {
        //     header('Location: ' . $vnp_Url);
        //     die();
        // } else {
        //     echo json_encode($returnData);
        // }
        // // vui lòng tham khảo thêm tại code demo
    }
}
