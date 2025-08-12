<?php

namespace Provider\PaymentGateway\Paypal;

use Dotenv\Dotenv;
use Exception;
use stdClass;

class Subscription extends App
{

    public function __construct($root = __DIR__ . '/../..')
    {
        parent::__construct($root);
        // initialize dot env
        Dotenv::createImmutable($this->app_root)->load();
    }

    public function plans(){
        $list_plans_url = 'https://'.($this->isSandbox() ? 'api.sandbox' : 'api').'.paypal.com/v1/billing/plans';
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->getLiveToken()->access_token];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $list_plans_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $plans_data = json_decode($response);

        return $plans_data;
    }

    public function getSubscriptionDetails($subscriptionId)
    {
        $url = 'https://' . ($this->isSandbox() ? 'api.sandbox' : 'api') . '.paypal.com/v1/billing/subscriptions/' . $subscriptionId;
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->getLiveToken()->access_token];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

    public function subscribe($plan, $subscriber, $return_url, $cancel_url)
    {
        $initiate_url = 'https://' . ($this->isSandbox() ? 'api.sandbox' : 'api') . '.paypal.com/v1/billing/subscriptions';
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->getLiveToken()->access_token];

        $curl_post_data = array(
            'plan_id' => $plan->id,
            'start_time' => date('Y-m-d\TH:i:s\Z', time() + 3600),
            'quantity' => '1', // Adjust quantity as needed
            'shipping_amount' => array(
                'currency_code' => 'USD',
                'value' => $plan->value
            ),
            'subscriber' => $subscriber,

            'application_context' => array(
                'brand_name' => 'walmart', // Adjust brand name as needed
                'locale' => 'en-US',
                'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                'user_action' => 'SUBSCRIBE_NOW',
                'payment_method' => array(
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
                ),
                'return_url' => 'https://enx316omyr0k.x.pipedream.net/',
                'cancel_url' => 'https://enx316omyr0k.x.pipedream.net/'
            )
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $initiate_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $subscription_data = json_decode($response);

        return $subscription_data;
    }

    public function cancelSubscription($subscriptionId)
    {
        $cancel_url = 'https://' . ($this->isSandbox() ? 'api.sandbox' : 'api') . '.paypal.com/v1/billing/subscriptions/' . $subscriptionId . '/cancel';
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->getLiveToken()->access_token];

        // Prepare cancel subscription data
        $cancel_data = [
            'reason' => 'Cancellation reason goes here', // Provide a reason for cancellation if desired
        ];

        // Initiate cancel subscription request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $cancel_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cancel_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
}
