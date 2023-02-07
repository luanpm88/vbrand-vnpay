<?php

namespace Acelle\Vnpay\Services;

use Acelle\Cashier\Interfaces\PaymentGatewayInterface;
use Acelle\Cashier\Library\TransactionVerificationResult;
use Acelle\Model\Transaction;
use Acelle\Vnpay\Vnpay;

class VnpayPaymentGateway implements PaymentGatewayInterface
{
    public $apiKey;
    public $secretKey;
    public $uri;

    public const TYPE = 'vnpay';

    /**
     * Construction
     */
    public function __construct($apiKey, $secretKey, $uri)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->uri = $uri;
    }

    public function getName() : string
    {
        return 'BaoKim';
    }

    public function getType() : string
    {
        return self::TYPE;
    }

    public function getDescription() : string
    {
        return trans('vnpay::messages.vnpay.description');
    }

    public function getShortDescription() : string
    {
        return trans('vnpay::messages.vnpay.short_description');
    }

    public function isActive() : bool
    {
        return ($this->publicKey && $this->secretKey);
    }

    public function getSettingsUrl() : string
    {
        return action("\Acelle\Vnpay\Controllers\VnpayController@settings");
    }

    public function getCheckoutUrl($invoice) : string
    {
        return action("\Acelle\Vnpay\Controllers\VnpayController@checkout", [
            'invoice_uid' => $invoice->uid,
        ]);
    }

    public function verify(Transaction $transaction) : TransactionVerificationResult
    {
        $invoice = $transaction->invoice;
        $vnpay = Vnpay::initialize();

        return $vnpay->runVerify($invoice);
    }
    

    public function allowManualReviewingOfTransaction() : bool
    {
        return false;
    }

    public function autoCharge($invoice)
    {
        $gateway = $this;
        $vnpay = Vnpay::initialize();

        $invoice->checkout($this, function($invoice) use ($vnpay) {
            try {
                // charge invoice
                $vnpay->pay($invoice);

                return new TransactionVerificationResult(TransactionVerificationResult::RESULT_DONE);
            } catch (\Exception $e) {
                return new TransactionVerificationResult(TransactionVerificationResult::RESULT_FAILED, $e->getMessage() .
                    '. <a href="' . $vnpay->gateway->getCheckoutUrl($invoice) . '">Click here</a> to manually charge.');
            }
        });
    }

    public function getAutoBillingDataUpdateUrl($returnUrl='/') : string
    {
        return \Acelle\Cashier\Cashier::lr_action("\Acelle\Vnpay\Controllers\VnpayController@autoBillingDataUpdate", [
            'return_url' => $returnUrl,
        ]);
    }

    public function supportsAutoBilling() : bool
    {
        return false;
    }

    /**
     * Check if service is valid.
     *
     * @return void
     */
    public function test()
    {
        $vnpay = Vnpay::initialize();
        $vnpay->test();
    }

    public function getMinimumChargeAmount($currency)
    {
        return 0;
    }
}
