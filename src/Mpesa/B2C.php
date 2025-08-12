<?php

namespace Provider\PaymentGateway\Mpesa;

use Dotenv\Dotenv;
use Exception;
use stdClass;

class B2C extends App {
    protected $callback_url = "";
    protected $amount = 0.00;
    protected $originator_conversation_id = "";
    protected $initiator_name = "";
    protected $security_credential = "";
    protected $command_id = "";
    protected $party_a = "";
    protected $party_b = "";
    protected $remarks = "business_payments";
    protected $transaction_type = "CustomerPayBillOnline";
    protected $occassion = "Default";
    protected $initiate_url = '';

    public function __construct($config)
    {
        parent::__construct($config);
        // initialize dot env
        $this->initiate_url='https://'.($this->isSandbox()?'sandbox':'api').'.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';
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

    public function setOriginatorConversationId($originator_conversation_id)
    {
        $this->originator_conversation_id = $originator_conversation_id;
        return $this;
    }

    public function setInitiatorName($initiator_name)
    {
        $this->initiator_name = $initiator_name;
        return $this;
    }

    public function setSecurityCredential($security_credential)
    {
        $this->security_credential = $this->encriptPassword($security_credential);
        return $this;
    }

    public function setCommandId($command_id)
    {
        $this->command_id = $command_id;
        return $this;
    }

    public function setPartyA($party_a)
    {
        $this->party_a = $party_a;
        return $this;
    }

    public function setPartyB($party_b)
    {
        $this->party_b = $party_b;
        return $this;
    }

    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;
        return $this;
    }

    public function setTransactionType($transaction_type)
    {
        $this->transaction_type = $transaction_type;
        return $this;
    }

    public function setOccasion($Occasion)
    {
        $this->occasion = $Occasion;
        return $this;
    }

    public function pay()
    {

        $stkheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $this->getLiveToken()->access_token];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->initiate_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);

        $curl_post_data = array(
            "OriginatorConversationID" => $this->originator_conversation_id,
            "InitiatorName" => $this->initiator_name,
            "SecurityCredential" =>$this->security_credential,
            "CommandID" => $this->command_id,
            "Amount" => $this->amount,
            "PartyA" => $this->party_a,
            "PartyB" =>$this->party_b,
            "Remarks" => $this->remarks,
            "QueueTimeOutURL" => $this->callback_url,
            "ResultURL" => $this->callback_url,
            "Occassion" => $this->occassion,
        );

        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        $curl_response = curl_exec($curl);

        return json_decode($curl_response);
    }

    public static function getCallbackData()
    {
        header("Content-type: application/json; charset=utf-8");
        date_default_timezone_set('Africa/Nairobi');
        $json = file_get_contents('php://input');

        $data = json_decode($json)->Result;

        $response = new stdClass();
        $response->status = $data->ResultCode;
        $response->OriginatorConversationID = $data->OriginatorConversationID;
        $response->ConversationID = $data->ConversationID;
        $response->ResultDesc = $data->ResultDesc;
        $response->TransactionID = $data->TransactionID;
        $response->fails = boolval($data->ResultCode);


        if (property_exists($data, "ResultParameters")) {
            $metadata = $data->ResultParameters->ResultParameter;
            foreach ($metadata as $val) {
                foreach ($val as $v) {
                    $name = $val->Key;
                    $response->$name = $v;
                }
            }
        }

        return $response;
    }
}