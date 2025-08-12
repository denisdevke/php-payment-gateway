<?php

namespace Provider\PaymentGateway\Paypal;

use Dotenv\Dotenv;
use Exception;
use stdClass;

class App
{
    protected $app_root = __DIR__ . '/../..';
    public function __construct($root = __DIR__ . '/../..')
    {
        $this->app_root = $root;
        // initialize dot env
        Dotenv::createImmutable($this->app_root)->load();
    }

    /**
     * @return bool returns false if PAYMENT_SANDBOX is set to false in .env
     */
    public function isSandbox(): bool{
        if(isset($_ENV['PAYMENT_SANDBOX'])){
            if(trim($_ENV['PAYMENT_SANDBOX'])=='true') return true;
        }
        return false;
    }

    // get live access token
    public function getLiveToken()
    {
        $headers = ['Accept: application/json; Content-Type:application/json; charset=utf8; Accept-Language: en_US'];
        $access_token_url = 'https://'.($this->isSandbox()?'api.sandbox':'api').'.paypal.com/v1/oauth2/token?grant_type=client_credentials';

        $curl = curl_init($access_token_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_USERPWD, $_ENV['PAYPAL_CLIENT_ID'] . ':' . $_ENV['PAYPAL_SECRET']);
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
}
