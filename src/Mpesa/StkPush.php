<?php

namespace Provider\PaymentGateway\Mpesa;

use Dotenv\Dotenv;
use Exception;
use stdClass;

class StkPush extends App
{
    protected $callback_url = "";
    protected $amount = 0.00;
    protected $phone = "";
    protected $short_code = "";
    protected $party_b = "";
    protected $reference = "";
    protected $description = "goods/servises payment";
    protected $transaction_type = "CustomerPayBillOnline";
    protected $initiate_url = '';
    public function __construct($config)
    {
        parent::__construct($config);
        $this->initiate_url='https://'.($this->isSandbox()?'sandbox':'api').'.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }



    public function getPassword($timestamp)
    {
        date_default_timezone_set('Africa/Nairobi');
        return base64_encode(
            $this->short_code .
            $this->config['PASS_KEY'] .
            $timestamp
        );
    }

    public function setCallbackUrl($url)
    {
        $this->callback_url = $url;
        return $this;
    }
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }
    public function setShortCode($short_code)
    {
        $this->short_code = $short_code;
        return $this;
    }
    public function setPartyB($part_b)
    {
        $this->party_b = $part_b;
        return $this;
    }
    public function setReference($ref)
    {
        $this->reference = $ref;
        return $this;
    }
    public function setRemarks($desc)
    {
        $this->description = $desc;
        return $this;
    }
    public function setTransactionType($type)
    {
        $this->transaction_type = $type;
        return $this;
    }

    // make buy goods stkpush
    public function tillRequestPush()
    {
        $stkheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $this->getLiveToken()->access_token];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->initiate_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);

        $timestamp = date('YmdHis');

        $curl_post_data = array(
            'BusinessShortCode' => $this->short_code,
            'Password' => $this->getPassword($timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $this->transaction_type,
            'Amount' => $this->amount,
            'PartyA' => $this->phone,
            'PartyB' => $this->party_b,
            'PhoneNumber' => $this->phone,
            'CallBackURL' => $this->callback_url,
            'AccountReference' => $this->reference,
            'TransactionDesc' => $this->description
        );

        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        $curl_response = curl_exec($curl);

        return json_decode($curl_response);
    }

    // get api callback data
    public static function getCallbackData()
    {
        header("Content-type: application/json; charset=utf-8");
        date_default_timezone_set('Africa/Nairobi');
        $json = file_get_contents('php://input');

        $data = json_decode($json)->Body->stkCallback;

        $response = new stdClass();
        $response->status = $data->ResultCode;
        $response->MerchantRequestID = $data->MerchantRequestID;
        $response->CheckoutRequestID = $data->CheckoutRequestID;
        $response->ResultDesc = $data->ResultDesc;
        $response->fails = boolval($data->ResultCode);

        if (property_exists($data, "CallbackMetadata")) {
            $metadata = $data->CallbackMetadata->Item;
            foreach ($metadata as $val) {
                foreach ($val as $v) {
                    $name = $val->Name;
                    $response->$name = $v;
                }
            }
        }

        return $response;
    }
}
