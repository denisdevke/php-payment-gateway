# PHP Payment Gateway
## PHP Payment Gateway Library

Makes it easy to integrate payment gateways to your PHP project.

Install using composer:

```bash
composer require denisdevke/payment-gateway
```

# M-Pesa Integration

## Configuring the App
The following shows the configurations required depending on the API you want to use:
```php
$config = [
    'CONSUMER_KEY' => 'your_consumer_key',
    'CONSUMER_SECRET' => 'your_consumer_secret',
    'PASS_KEY' => 'your_passkey', // required for STK push
    'SANDBOX' => true, // set to false for production
];
```

## Make an STK Push for Buy Goods

### Initiating the Request

First import the dependency to your PHP class, create an instance of StkPush, then make your request as shown in the code below:

```php


// Import the dependency
use Provider\PaymentGateway\Mpesa\StkPush;

// You can use the code below to format user phone number
$phone = preg_replace('/^(?:\+?254|0)?/', '254', $phone_number);

$stkPush = new StkPush($config);
$stkPush->setCallbackUrl('https://your-domain.com/callback') // required
    ->setAmount(100) // required - amount in KES
    ->setPhone('254712345678') // required - format: 254XXXXXXXXX
    ->setPartyB('174379') // shortcode or till number
    ->setShortCode('174379') // business shortcode
    ->setReference('ORDER123') // required - reference number
    ->setRemarks('Payment for goods'); // optional

$response = $stkPush->tillRequestPush();

```

After the request is submitted, the following response will be returned from M-Pesa.

**Keep in mind that $response is an object of stdClass**. Below is an example of how you can access response properties.

To access MerchantRequestID for example: `$response->MerchantRequestID`

```json
{
  "MerchantRequestID": "119215-30497********",
  "CheckoutRequestID": "ws_CO_18022023203127******",
  "ResponseCode": "0",
  "ResponseDescription": "Success. Request accepted for processing",
  "CustomerMessage": "Success. Request accepted for processing"
}
```

### Accessing the Callback

On every request you make, M-Pesa will make a request to your API URL that you provided.

The library makes it easy for you to access the callback data:

```php
// First import the dependencies
use Provider\PaymentGateway\Mpesa\StkPush;

// Get the callback data
$callbackData = StkPush::getCallbackData();
```

Below is the format of data returned. **Don't forget that it is stdClass, don't confuse with array:**

```json
{
  "status": 0,
  "MerchantRequestID": "119215-30497********",
  "CheckoutRequestID": "ws_CO_1802202319494*******",
  "ResultDesc": "The service request is processed successfully.",
  "fails": false,
  "Amount": 1,
  "MpesaReceiptNumber": "RBI6******",
  "TransactionDate": 20230218194956,
  "PhoneNumber": "254724******"
}
```

## Making B2C Request
### API Request
Reference the Daraja API documentation to understand the payload being passed:
https://developer.safaricom.co.ke/APIs/BusinessToCustomer
```php
use Provider\PaymentGateway\Mpesa\B2C;

$b2c = new B2C($config);
$uniqueId = uniqid('', true);

$b2c->setCallbackUrl('https://your-domain.com/b2c-callback')
    ->setAmount(1000)
    ->setOriginatorConversationId($uniqueId)
    ->setInitiatorName('your_initiator_name') // API username
    ->setSecurityCredential('your_password') // This will be encrypted automatically
    ->setCommandId('BusinessPayment') // or SalaryPayment, BusinessPayment, PromotionPayment
    ->setPartyA('600XXX') // shortcode/paybill number
    ->setPartyB('254712345678') // recipient phone number
    ->setRemarks('Payment for services')
    ->setOccasion('Monthly payment');

$response = $b2c->pay();
echo json_encode($response);

```

### API Callback
Use the following code to get the result from API callback:
```php
use Provider\PaymentGateway\Mpesa\B2C;

$callbackData = B2C::getCallbackData();
echo json_encode($callbackData);
```
#### Failed request
```json
{
  "status": 2001,
  "OriginatorConversationID": "4ad2-40f1-8898-cef301c69e1c20604259",
  "ConversationID": "AG_20240809_*****",
  "ResultDesc": "The initiator information is invalid.",
  "TransactionID": "SH****97H",
  "fails": true
}
```
#### Success request
```json
{
  "status": 0,
  "OriginatorConversationID": "f0a2-4425-aa4b-1d01d2ea19ea127909458",
  "ConversationID": "AG_20240809_*****",
  "ResultDesc": "The service request is processed successfully.",
  "TransactionID": "SH****PVG",
  "fails": false,
  "TransactionAmount": 1000,
  "TransactionReceipt": "SH96****",
  "ReceiverPartyPublicName": "2547*******75 - D*** ***",
  "TransactionCompletedDateTime": "09.08.2024 15:38:35",
  "B2CUtilityAccountAvailableFunds": 1152.03,
  "B2CWorkingAccountAvailableFunds": 0,
  "B2CRecipientIsRegisteredCustomer": "Y",
  "B2CChargesPaidAccountAvailableFunds": 0
}
```
# PayPal Integration

First you need to configure your environment by adding the following to your `.env` file:
```dotenv
PAYPAL_CLIENT_ID="your_paypal_client_id"
PAYPAL_SECRET="your_paypal_secret"
PAYMENT_SANDBOX=true
```
You can always switch between live and sandbox by updating `PAYMENT_SANDBOX=true` to `false`.

## Subscription

To subscribe users to your system using PayPal, follow the steps below:

### Import the Dependencies
```php
use Provider\PaymentGateway\Paypal\Subscription;
```

### Create the Class Instance
```php
$paypalSubscription = new Subscription('/path/to/your/project'); // Path to your project root where .env is located
```

### Subscribe the User
```php
// Define the subscriber
$subscriber = [
    'name' => [
        'given_name' => 'John',
        'surname' => 'Doe'
    ],
    'email_address' => 'customer@example.com',
    'shipping_address' => [
        'name' => [
            'full_name' => 'John Doe'
        ],
        'address' => [
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 1',
            'admin_area_2' => 'San Jose',
            'admin_area_1' => 'CA',
            'postal_code' => '95131',
            'country_code' => 'US'
        ]
    ]
];

// Specify your plan (create the plan on PayPal first and get the plan ID)
$plan = new stdClass();
$plan->id = 'P-9PP51961TH493415EMW4NX5A'; // Your actual plan ID
$plan->value = '18'; // Plan amount

// Create the subscription
$subscription = $paypalSubscription->subscribe(
    $plan,
    $subscriber,
    'https://your-domain.com/paypal/success', // return URL
    'https://your-domain.com/paypal/cancel'   // cancel URL
);

```
Sample response
```json

{
  "status": "APPROVAL_PENDING",
  "id": "I-25GFPAT3X2MT",
  "create_time": "2024-01-30T12:21:02Z",
  "links": [
    {
      "href": "https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-0RL61114E6855090G",
      "rel": "approve",
      "method": "GET"
    },
    {
      "href": "https://api.sandbox.paypal.com/v1/billing/subscriptions/I-25GFPAT3X2MT",
      "rel": "edit",
      "method": "PATCH"
    },
    {
      "href": "https://api.sandbox.paypal.com/v1/billing/subscriptions/I-25GFPAT3X2MT",
      "rel": "self",
      "method": "GET"
    }
  ]
}
```

Now direct the user to the approval URL. The user will be redirected to the return URL you provided after approval.

### Check if Subscription was Successful
```php
$subscriptionDetails = $paypalSubscription->getSubscriptionDetails('SUBSCRIPTION_ID');
if ($subscriptionDetails->status === 'ACTIVE') {
    // Subscription was successfully approved and activated
    // Update database with subscription status
    echo 'Subscription is active!';
} else {
    // Subscription approval failed or encountered an error
    // Handle the error appropriately
    echo 'Subscription failed or pending approval';
}
```

### Cancel Subscription
```php
$response = $paypalSubscription->cancelSubscription('SUBSCRIPTION_ID');
```

### Get All Plans
```php
$plans = $paypalSubscription->plans();
echo json_encode($plans);
```