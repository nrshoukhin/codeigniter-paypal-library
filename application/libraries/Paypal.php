<?php
if (!defined('BASEPATH')) exit('No direct script access allowed'); 

require_once APPPATH."/third_party/PayPal-PHP-SDK/autoload.php";

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;
use PayPal\Api\Agreement;
use PayPal\Api\Payer;
use PayPal\Api\ShippingAddress;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;

class Paypal{

	private $apiContext = '';

	private $plan = '';

	private $paymentDefinition = '';

	private $chargeModel = '';

	private $merchantPreferences = '';

	private $createdPlan = '';

	private $patch = '';

	private $patchRequest = '';

	private $patchedPlan = '';

	private $agreement = '';

	private $shippingAddress = '';

    public function __construct() {
    	$CI =& get_instance();
    	$CI->config->load('paypal');
    }

    public function set_api_context(){

    	$this->apiContext = new \PayPal\Rest\ApiContext(
    			  new \PayPal\Auth\OAuthTokenCredential(
    			    config_item("client_id"),
    			    config_item("client_secret")
    			  )
    			);

        $this->apiContext->setConfig(
                array(
                    'mode' => config_item("mode"),
                    'log.LogEnabled' => true,
                    'log.FileName' => '../PayPal.log',
                    'log.LogLevel' => 'DEBUG',
                    'cache.enabled' => true
                )
            );

    	return;
    }


    /* Creates a billing plan. In the JSON request body, include the plan details. A plan must include at least one regular payment definition and, optionally, a trial payment definition. Each payment definition specifies a billing period, which determines how often and for how long the customer is charged. A plan can specify a fixed or infinite number of payment cycles. A payment definition can optionally specify shipping fee and tax amounts. The default state of a new plan is CREATED. Before you can create an agreement from a plan, you must activate the plan by updating its state to ACTIVE.
	
	name string required
	The plan name.
	Maximum length: 128.

	description string required
	The plan description. Maximum length is 127 single-byte alphanumeric characters.
	Maximum length: 127.

	type enum required
	The plan type. Indicates whether the payment definitions in the plan have a fixed number of or infinite payment cycles. Value is:
	FIXED. The plan has a fixed number of payment cycles.
	INFINITE. The plan has infinite, or 0, payment cycles.
	Allowed values: FIXED, INFINITE.

	Maximum length: 20.
    */

    public function set_plan( $plan_name, $plan_description, $plan_type ){

    	$this->plan = new Plan();
    	$this->plan->setName($plan_name)
    	    ->setDescription($plan_description)
    	    ->setType($plan_type);

    	return;

    }

    // Set billing plan definitions
    /*
    name string required
	The payment definition name.
	Maximum length: 128.

	type enum required
	The payment definition type. Each plan must have at least one regular payment definition and, optionally, a trial payment definition. Each definition specifies how often and for how long the customer is charged.
	Possible values: TRIAL, REGULAR.

	frequency enum required
	The frequency of the payment in this definition.
	Possible values: WEEK, DAY, YEAR, MONTH.

	frequency_interval string required
	The interval at which the customer is charged. Value cannot be greater than 12 months.

	cycles string required
	The number of payment cycles. For infinite plans with a regular payment definition, set cycles to 0.

	amount object required
	The currency and amount of the charge to make at the end of each payment cycle for this definition.

	currency string required
	The three-character ISO-4217 currency code ( https://developer.paypal.com/docs/integration/direct/rest/currency-codes/ ).

	value string required
	The value, which might be:
	An integer for currencies like JPY that are not typically fractional.
	A decimal fraction for currencies like TND that are subdivided into thousandths.
	For the required number of decimal places for a currency code, see Currency codes - ISO 4217 ( https://www.iso.org/iso-4217-currency-codes.html ).
    */

    public function set_billing_plan_definition( $definition, $type, $frequency, $frequncy_interval, $cycles, $price ){

    	$this->paymentDefinition = new PaymentDefinition();
    	$this->paymentDefinition->setName( $definition )
    	    ->setType($type)//TRIAL or REGULAR
    	    ->setFrequency($frequency)
    	    ->setFrequencyInterval($frequncy_interval)
    	    ->setCycles($cycles)
    	    ->setAmount(new Currency(array( 'value' => $price, 'currency' => config_item("currency") )));

    	$this->set_payment_definition();

    	return;

    }

    // Set charge models
    // Information of shipping fees and taxes.

    /*
    type enum required
    The charge model type, which is tax or shipping.
    Possible values: TAX, SHIPPING.
    Maximum length: 20.

	currency string required
	The three-character ISO-4217 currency code ( https://developer.paypal.com/docs/integration/direct/rest/currency-codes/ ).

	value string required
	The value, which might be:
	An integer for currencies like JPY that are not typically fractional.
	A decimal fraction for currencies like TND that are subdivided into thousandths.
	For the required number of decimal places for a currency code, see Currency codes - ISO 4217 ( https://www.iso.org/iso-4217-currency-codes.html ).
    */

    public function set_charge_models($type, $price){

    	$this->chargeModel = new ChargeModel();
    	$this->chargeModel->setType($type)->setAmount(new Currency(array(
    	    'value' => $price,
    	    'currency' => config_item("currency")
    	)));
    	$this->paymentDefinition->setChargeModels(array(
    	    $this->chargeModel
    	));

    	$this->set_payment_definition();

    	return;

    }

    //Call this function after setting payment definition and charge models ( if needed )
    public function set_payment_definition(){

    	$this->plan->setPaymentDefinitions(array(
    	    $this->paymentDefinition
    	));

    	return;

    }

    // Set merchant preferences
    /*
    The merchant preferences for a plan, which define how much it costs to set up the agreement, the URLs where the customer can approve or cancel the agreement, the maximum number of allowed failed payment attempts, whether PayPal automatically bills the outstanding balance in the next billing cycle, and the action if the customer's initial payment fails.

    return_url: redirect URL after creating a successfull agreement.

    cancel_url: redirect URL after cancelling an agreement.

    auto_bill_amount: Indicates whether PayPal automatically bills the outstanding balance in the next billing cycle. The outstanding balance is the total amount of any previously failed scheduled payments. Value is:
		NO. PayPal does not automatically bill the customer the outstanding balance.
		YES. PayPal automatically bills the customer the outstanding balance.

		Default: NO.

	initial_fail_amount_action: The action if the customer's initial payment fails. Value is:
		CONTINUE. The agreement remains active and the failed payment amount is added to the outstanding balance. If auto-billing is enabled, PayPal automatically bills the outstanding balance in the next billing cycle.
		CANCEL PayPal creates the agreement but sets its state to pending until the initial payment clears. If the initial payment clears, the pending agreement becomes active. If the initial payment fails, the pending agreement is canceled.

		Default: CONTINUE.

		Note: You can use the setup_fee value as the initial amount to trigger the initial_fail_amount_action.

	max_fail_attempts: The maximum number of allowed failed payment attempts. The default value, which is 0, defines infinite failed payment attempts.

	setup_fee: The currency and amount of the set-up fee for the agreement. This fee is the initial, non-recurring payment amount that is due immediately when the billing agreement is created. Can be used as the initial amount to trigger the initial_fail_amount_action. The default for the amount is 0.
     */

    public function set_merchant_preferences( $return_url, $cancel_url, $auto_bill_amount = "NO", $initial_fail_amount_action = "CANCEL", 
    	$max_fail_attempts = 0, $agreement_fees = 0 ){

    	$this->merchantPreferences = new MerchantPreferences();
    	$this->merchantPreferences->setReturnUrl($return_url)
    	  ->setCancelUrl($cancel_url)
    	  ->setAutoBillAmount($auto_bill_amount)
    	  ->setInitialFailAmountAction($initial_fail_amount_action)
    	  ->setMaxFailAttempts($max_fail_attempts)
    	  ->setSetupFee( new Currency(array('value' => $agreement_fees, 'currency' => config_item("currency") )));

    	$this->plan->setMerchantPreferences( $this->merchantPreferences );

    	return;

    }

    /*
    line1 string required
    The first line of the address. For example, number or street.

    city string required
    The city name.

    state string
    The code ( https://developer.paypal.com/docs/api/reference/state-codes/ ) for a US state or the equivalent for other countries. Required for transactions if the address is in one of these countries: Argentina, Brazil, Canada, China, India, Italy, Japan, Mexico, Thailand, or United States. Maximum length is 40 single-byte characters.

    postal_code string
    The postal code, which is the zip code or equivalent. Typically required for countries with a postal code or an equivalent. 
    */

    public function set_shipping_address( $line1, $city, $state, $postal_code, $country_code ){

    	// Adding shipping details
    	$this->shippingAddress = new ShippingAddress();
    	$this->shippingAddress->setLine1($line1)
    	    ->setCity($city)
    	    ->setState($state)
    	    ->setPostalCode($postal_code)
    	    ->setCountryCode($country_code);

    	return;

    }

    //Before calling this function set the shipping address
    public function create_and_activate_billing_plan( $agreement_name, $agreement_description ){

    	try {
    	    $this->createdPlan = $this->plan->create($this->apiContext);
    	    
    	    try {

    	    	/*
    	    	state enum
    	    	The plan status.
    	    	Read only.

    	    	Possible values: CREATED, ACTIVE, INACTIVE.

    	    	Default: CREATED.

    	    	op enum required
    	    	The operation. The possible values are:

    	    	add. Depending on the target location reference, completes one of these functions:

    	    	  The target location is an array index. Inserts a new value into the array at the specified index.

    	    	  The target location is an object parameter that does not already exist. Adds a new parameter to the object.

    	    	  The target location is an object parameter that does exist. Replaces that parameter's value.

    	    	  The value parameter defines the value to add. For more information, see 4.1. add ( https://tools.ietf.org/html/rfc6902#section-4.1 ).

    	    	remove. Removes the value at the target location. For the operation to succeed, the target location must exist. For more information, see 4.2. remove ( https://tools.ietf.org/html/rfc6902#section-4.2 ).

    	    	replace. Replaces the value at the target location with a new value. The operation object must contain a value parameter that defines the replacement value. For the operation to succeed, the target location must exist. For more information, see 4.3. replace ( https://tools.ietf.org/html/rfc6902#section-4.3 ).

    	    	move. Removes the value at a specified location and adds it to the target location. The operation object must contain a from parameter, which is a string that contains a JSON pointer value that references the location in the target document from which to move the value. For the operation to succeed, the from location must exist. For more information, see 4.4. move ( https://tools.ietf.org/html/rfc6902#section-4.4 ).

    	    	copy. Copies the value at a specified location to the target location. The operation object must contain a from parameter, which is a string that contains a JSON pointer value that references the location in the target document from which to copy the value. For the operation to succeed, the from location must exist. For more information, see 4.5. copy ( https://tools.ietf.org/html/rfc6902#section-4.5 ).

    	    	test. Tests that a value at the target location is equal to a specified value. The operation object must contain a value parameter that defines the value to compare to the target location's value. For the operation to succeed, the target location must be equal to the value value. For test, equal indicates that the value at the target location and the value that value defines are of the same JSON type. 

    	    	path string
    	    	The JSON Pointer to the target document location at which to complete the operation.

    	    	value number,integer,string,boolean,null,array,object
    	    	The value to apply. The remove operation does not require a value.
    	    	*/

    	        $this->patch = new Patch();
    	        $value = new PayPalModel('{"state":"ACTIVE"}');
    	        $this->patch->setOp('replace')
    	            ->setPath('/')
    	            ->setValue($value);
    	        $this->patchRequest = new PatchRequest();
    	        $this->patchRequest->addPatch($this->patch);
    	        $this->createdPlan->update($this->patchRequest, $this->apiContext);
    	        $this->patchedPlan = Plan::get($this->createdPlan->getId(), $this->apiContext);
    	        
    	        $this->create_new_agreement( $agreement_name, $agreement_description );

    	        return;

    	    } catch (PayPal\Exception\PayPalConnectionException $ex) {
    	        echo $ex->getCode();
    	        echo $ex->getData();
    	        die($ex);
    	    } catch (Exception $ex) {
    	        die($ex);
    	    }
    	} catch (PayPal\Exception\PayPalConnectionException $ex) {
    	    echo $ex->getCode();
    	    echo $ex->getData();
    	    die($ex);
    	} catch (Exception $ex) {
    	    die($ex);
    	}

    }

    /*
    name string required
    The agreement name.
    Maximum length: 128.

    description string required
    The agreement description.
    Maximum length: 128.

    start_date string required
    The date and time when this agreement begins, in Internet date and time format. The start date must be no less than 24 hours after the current date as the agreement can take up to 24 hours to activate.

    The start date and time in the create agreement request might not match the start date and time that the API returns in the execute agreement response. When you execute an agreement, the API internally converts the start date and time to the start of the day in the time zone of the merchant account. For example, the API converts a 2017-01-02T14:36:21Z start date and time for an account in the Berlin time zone (UTC + 1) to 2017-01-02T00:00:00. When the API returns this date and time in the execute agreement response, it shows the converted date and time in the UTC time zone. So, the internal 2017-01-02T00:00:00 start date and time becomes 2017-01-01T23:00:00 externally.

    plan object required
    The ID of the plan on which this agreement is based.

    payment_method enum required
    The payment method.
    Possible values: bank, paypal.

    payer object required
    The details for the customer who funds the payment. The API gathers this information from execution of the approval URL.

    shipping_address object
    The shipping address for a payment. Must be provided if it differs from the default address.
    */
    public function create_new_agreement( $agreement_name, $agreement_description ){

    	// Create new agreement
    	$startDate = date('c', time() + 3600);
    	$this->agreement = new Agreement();
    	$this->agreement->setName($agreement_name)
    	    ->setDescription($agreement_description)
    	    ->setStartDate($startDate);

    	// Set plan id
    	$plan = new Plan();
    	$plan->setId( $this->patchedPlan->getId() );
    	$this->agreement->setPlan($plan);

    	// Add payer type
    	$payer = new Payer();
    	$payer->setPaymentMethod('paypal');
    	$this->agreement->setPayer($payer);

    	$this->agreement->setShippingAddress($this->shippingAddress);

    	try {
    	    // Create agreement
    	    $agreement = $this->agreement->create($this->apiContext);
    	    
    	    // Extract approval URL to redirect user
    	    $approvalUrl = $this->agreement->getApprovalLink();
    	    
    	    header("Location: " . $approvalUrl);
    	    exit();
    	} catch (PayPal\Exception\PayPalConnectionException $ex) {
    	    echo $ex->getCode();
    	    echo $ex->getData();
    	    die($ex);
    	} catch (Exception $ex) {
    	    die($ex);
    	}

    }

    public function execute_agreement( $success_token ){
    	    
    	try {
    	    // Execute agreement
            $this->set_api_context();
            $agreement         = new Agreement();
    	    $agreement_success = $agreement->execute( $success_token, $this->apiContext );

    	} catch (PayPal\Exception\PayPalConnectionException $ex) {
    	    echo $ex->getCode();
    	    echo $ex->getData();
    	    die($ex);
    	} catch (Exception $ex) {
    	    die($ex);
    	}

    }

    public function cancel_subscription( $agreementId ){
        $this->set_api_context();                  
        $agreement = new Agreement();            

        $agreement->setId($agreementId);
        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Cancel the agreement");

        try {
            $agreement->cancel($agreementStateDescriptor, $this->apiContext);
            $cancelAgreementDetails = Agreement::get( $agreement->getId(), $this->apiContext );
            return;                
        } catch (Exception $ex) {
            die($ex);                  
        }
    }


    //------------ These functions for paypal payment -------------------------\\

    public function create_payment( $payment_method, $return_url, $cancel_url, 
        $total, $description, $intent )
    {

        // Create new payer and method
        /*
        payment_method enum required
        The payment method.
        Possible values: bank, paypal.
        */
        $payer = new Payer();
        $payer->setPaymentMethod($payment_method);

        // Set redirect URLs
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($return_url)
          ->setCancelUrl($cancel_url);

        // Set payment amount
        $amount = new Amount();
        $amount->setCurrency( config_item("currency") )
          ->setTotal($total);

        // Set transaction object
        /*
        description string
        The purchase description.
        Maximum length: 127.
        */
        $transaction = new Transaction();
        $transaction->setAmount($amount)
          ->setDescription($description);

        // Create the full payment object
        /*
        intent enum required
        The payment intent. Value is:
        sale. Makes an immediate payment.
        authorize. Authorizes a payment for capture later.
        order. Creates an order.
        Allowed values: sale, authorize, order
        */
        $payment = new Payment();
        $payment->setIntent($intent)
          ->setPayer($payer)
          ->setRedirectUrls($redirectUrls)
          ->setTransactions(array($transaction));

        // Create payment with valid API context
        try {
            $payment->create($this->apiContext);

            // Get PayPal redirect URL and redirect the customer
            $approvalUrl = $payment->getApprovalLink();

            header("Location: " . $approvalUrl);
            exit();

          // Redirect the customer to $approvalUrl
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
          echo $ex->getCode();
          echo $ex->getData();
          die($ex);
        } catch (Exception $ex) {
          die($ex);
        }

    }

    public function execute_payment( $payment_id, $payer_id ){

        $this->set_api_context();
        // Get payment object by passing paymentId
        $paymentId = $payment_id;

        $payment = Payment::get($paymentId, $this->apiContext);
        $payerId = $payer_id;

        // Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
          // Execute payment
          $result = $payment->execute($execution, $this->apiContext);
          return;
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
          echo $ex->getCode();
          echo $ex->getData();
          die($ex);
        } catch (Exception $ex) {
          die($ex);
        }
    }

}