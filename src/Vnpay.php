<?php

namespace Acelle\Vnpay;

use Acelle\Model\Setting;
use Acelle\Cashier\Library\TransactionVerificationResult;
use Acelle\Vnpay\Services\VnpayPaymentGateway;
use Acelle\Cashier\Library\AutoBillingData;
use Firebase\JWT\JWT;

class Vnpay
{
    public const NAME = 'acelle/vnpay';

    const TOKEN_EXPIRE = 86400;

    public $gateway;

    public $_jwt;

    public function __construct()
    {
        $apiKey = env('BAOKIM_API_KEY','');
        $secretKey = env('BAOKIM_SECRET_KEY','');
        $uri = env('BAOKIM_URI','');

        $this->gateway = new VnpayPaymentGateway($apiKey, $secretKey, $uri);
    }

    public static function initialize()
    {
        return (new self());
    }

    /**
     * Request PayPal service.
     *
     * @return void
     */
    private function request($uri, $type = 'GET', $options = [])
    {
        $client = new \GuzzleHttp\Client();
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $response = $client->request($type, $uri, [
            'headers' => $headers,
            'query' => isset($options['query']) ? $options['query'] : [],
            'form_params' => isset($options['form_params']) ? $options['form_params'] : [],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function createOrder($data)
    {
        return $this->request($this->gateway->uri . '/api/v5/order/send', 'POST', [
            'query' => [
                'jwt' => $this->getToken(),
            ],
            'form_params' => $data,
        ]);
    }

    public function getKey()
    {
        return $this->_jwt;
    }

    public function refreshToken($key, $sec){

		$tokenId    = base64_encode(random_bytes(32));
		$issuedAt   = time();
		$notBefore  = $issuedAt;
		$expire     = $notBefore + self::TOKEN_EXPIRE;

		/*
		 * Payload data of the token
		 */
		$data = [
			'iat'  => $issuedAt,         // Issued at: time when the token was generated
			'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
			'iss'  => $key,     // Issuer
			'nbf'  => $notBefore,        // Not before
			'exp'  => $expire,           // Expire
			'form_params' => [
			]
		];

		/*
		 * Encode the array to a JWT string.
		 * Second parameter is the key to encode the token.
		 *
		 * The output string can be validated at http://jwt.io/
		 */
		$this->_jwt = JWT::encode(
			$data,      //Data to be encoded in the JWT
			$sec, // The signing key
			'HS256'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
		);

		return $this->_jwt;
	}

	/**
	 * Get JWT
	 */
	public function getToken(){
		if(!$this->_jwt)
        $this->refreshToken($this->gateway->apiKey, $this->gateway->secretKey);

		try {
			JWT::decode($this->_jwt, $this->gateway->secretKey, array('HS256'));
		}catch(\Exception $e){
			$this->refreshToken($this->gateway->apiKey, $this->gateway->secretKey);
		}

		return $this->_jwt;
	}
}
