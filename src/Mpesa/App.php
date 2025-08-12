<?php

namespace Provider\PaymentGateway\Mpesa;

use Dotenv\Dotenv;
use Exception;
use stdClass;

class App
{
    protected $config = [
        'CONSUMER_KEY' => '',
        'CONSUMER_SECRET' => '',
        'SHORT_CODE' => '',
        'PASS_KEY' => '',
    ];

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return bool returns false if PAYMENT_SANDBOX is set to false in .env
     */
    public function isSandbox(): bool{
        if(isset($this->config['SANDBOX'])){
            if($this->config['SANDBOX']) return true;
        }
        return false;
    }

    // get live access token
    public function getLiveToken()
    {
        $headers = ['Content-Type:application/json; charset=utf8'];
        $access_token_url = 'https://'.($this->isSandbox()?'sandbox':'api').'.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $curl = curl_init($access_token_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_USERPWD, $this->config['CONSUMER_KEY'] . ':' . $this->config['CONSUMER_SECRET']);
        curl_close($curl);
        try {

            $result = curl_exec($curl);
            $data = null;
            if (json_decode($result) == null) {
                $data = new stdClass();
                $data->access_token = "";
            } else $data = json_decode($result);
            $data->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            return $data;
        } catch (Exception $e) {
            $data->status = 500;
            $data->error = $e->getMessage();
            return $data;
        }
    }

    public function encriptPassword($pass){
        $publicKey = file_get_contents(__DIR__.'/public.cer');
        openssl_public_encrypt($pass, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);;
    }
}
