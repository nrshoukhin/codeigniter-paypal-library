<?php
class Payment extends CI_Controller {
	 
	public function __construct(){
		parent::__construct();
		$this->load->library("paypal");
		$this->load->helper("url");
	}


	public function index(){

		$this->load->view("index");
		return;

	}


	public function subscribe(){

		if ( !empty($_POST["plan_name"]) && !empty($_POST["plan_description"]) ) {

			$this->paypal->set_api_context();

			$this->paypal->set_plan( $_POST["plan_name"], $_POST["plan_description"], "INFINITE" );

			$definition = "Regular Payments";
			$type       = "REGULAR";
			$frequency  = "MONTH";
			$frequncy_interval = '1';
			$cycles = 0;
			$price = "49";

			$this->paypal->set_billing_plan_definition( $definition, $type, $frequency, $frequncy_interval, $cycles, $price );

			$returnurl = base_url()."index.php/payment/success";
			$cancelurl = base_url()."index.php/payment/cancel";

			$this->paypal->set_merchant_preferences( $returnurl, $cancelurl );

			$line1 = "Street - 1, Sector - 1";
			$city  = "Dhaka";
			$state = "Dhaka";
			$postalcode = "12345";
			$country = "AU";

			$this->paypal->set_shipping_address( $line1, $city, $state, $postalcode, $country );

			$agreement_name = "Payment Agreement Name";
			$agreement_description = "Payment Agreement Description";

			$this->paypal->create_and_activate_billing_plan( $agreement_name, $agreement_description );

		}

	}

	public function cancel(){
		$this->index();
		return;
	}

	//After successfully create an agreement we will be redirected to this function
	public function success(){

		if ( !empty( $_GET['token'] ) ) {

		    $token = $_GET['token'];
		    $this->paypal->execute_agreement( $token );
		    $this->index();

		}

		return;

	}

	public function create_payment(){

		$this->paypal->set_api_context();

		$payment_method = "paypal";
		$return_url     = base_url()."index.php/payment/success_payment";
		$cancel_url     = base_url()."index.php/payment/cancel";
		$total          = 10;
		$description    = "Paypal product payment";
		$intent         = "sale";

		$this->paypal->create_payment( $payment_method, $return_url, $cancel_url, 
        $total, $description, $intent );

        return;

	}

	//After creating a payment successfully we will be redirected here
	public function success_payment(){

		if ( !empty( $_GET['paymentId'] ) && !empty( $_GET['PayerID'] ) ) {

		    $this->paypal->execute_payment( $_GET['paymentId'], $_GET['PayerID'] );
		    $this->index();

		}

		return;

	}
	
}