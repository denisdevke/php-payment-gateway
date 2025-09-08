<?php

namespace Provider\PaymentGateway\Mpesa;

use Dotenv\Dotenv;
use Exception;
use stdClass;
use Provider\PaymentGateway\Logger;

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
        
        // Initialize logger
        Logger::init();
        
        $rawContent = file_get_contents('php://input');
        
        Logger::debug('STK Push callback raw content received', ['raw_content' => $rawContent]);
        
        if (empty($rawContent)) {
            Logger::error('Empty request body received for STK Push callback');
            throw new Exception('Empty request body');
        }

        $requestData = json_decode($rawContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Invalid JSON received for STK Push callback', [
                'json_error' => json_last_error_msg(),
                'raw_content' => $rawContent
            ]);
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        // Extract the actual callback data from nested structure
        // M-Pesa sends: {"Body": {"stkCallback": {...actual data...}}}
        if (!isset($requestData['Body']['stkCallback'])) {
            Logger::error('Missing stkCallback in request body', ['request_data' => $requestData]);
            throw new Exception('Missing stkCallback in request body');
        }

        $data = (object) $requestData['Body']['stkCallback'];

        if (!isset($data->MerchantRequestID)) {
            Logger::error('Missing required field: MerchantRequestID', ['stk_callback_data' => $data]);
            throw new Exception('Missing required field: MerchantRequestID');
        }

        $response = new stdClass();
        $response->status = $data->ResultCode ?? null;
        $response->MerchantRequestID = $data->MerchantRequestID;
        $response->CheckoutRequestID = $data->CheckoutRequestID ?? null;
        $response->ResultDesc = $data->ResultDesc ?? null;
        $response->fails = boolval($data->ResultCode ?? 1);

        // Parse callback metadata if payment was successful (ResultCode 0)
        if (isset($data->ResultCode) && $data->ResultCode == 0 && isset($requestData['Body']['stkCallback']['CallbackMetadata']['Item'])) {
            Logger::info('STK Push payment successful', [
                'merchant_request_id' => $data->MerchantRequestID,
                'checkout_request_id' => $data->CheckoutRequestID ?? null,
                'result_desc' => $data->ResultDesc ?? null
            ]);
            
            $callbackData = self::parseCallbackMetadata($requestData['Body']['stkCallback']);
            
            Logger::debug('STK Push callback metadata parsed', ['metadata' => $callbackData]);
            
            // Add parsed metadata to response
            foreach ($callbackData as $key => $value) {
                $response->$key = $value;
            }
        } else {
            Logger::warning('STK Push payment failed or incomplete', [
                'merchant_request_id' => $data->MerchantRequestID,
                'result_code' => $data->ResultCode ?? null,
                'result_desc' => $data->ResultDesc ?? null
            ]);
        }

        Logger::info('STK Push callback processed successfully', [
            'merchant_request_id' => $data->MerchantRequestID,
            'result_code' => $data->ResultCode ?? null
        ]);

        return $response;
    }

    /**
     * Parse M-Pesa CallbackMetadata into a flat array
     *
     * @param array $stkCallback
     * @return array
     */
    private static function parseCallbackMetadata(array $stkCallback): array
    {
        $metadata = [];

        if (isset($stkCallback['CallbackMetadata']['Item'])) {
            foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
                if (isset($item['Name']) && isset($item['Value'])) {
                    $metadata[$item['Name']] = $item['Value'];
                }
            }
        }

        return $metadata;
    }
}
