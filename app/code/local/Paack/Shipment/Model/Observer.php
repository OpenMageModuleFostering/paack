<?php

class Paack_Shipment_Model_Observer {
	const TEST_API       = "http://test.api.paack.co/api/public/v1/orders";
	const PRODUCTION_API = "http://api.paack.co/api/public/v1/orders";
	public function sendOrderJson(Varien_Event_Observer $observer){
		Mage::log('Create order');
		$data = Mage::getStoreConfig('paacksettings/settings/api_key');
		$event = $observer->getEvent();
	    $shipment = $event->getShipment();    
	    $order = $observer->getEvent()->getOrder();
	    
	    if($order->getShippingMethod() == "paack_shipping_")
		    $this->postToShipmentAPI($order);
	}

	public function postToShipmentAPI($order){
		Mage::log('start creating shipment');
	    $shippingAddress = $order->getShippingAddress()->getData();
	    $weightUnit = $order->getWeightUnit();

	    $api_key  = Mage::getStoreConfig('paacksettings/settings/api_key');
	    $name     = $shippingAddress['firstname'].' '.$shippingAddress['lastname'];
		$email    = $shippingAddress['email'];
		$phone    = $shippingAddress['telephone'];
		$store_id = Mage::getStoreConfig('paacksettings/settings/store_id');
		$retailer_order_number = $order->getRealOrderId();
		$delivery_address = array();
		$delivery_address['address']     = $shippingAddress['company'].' '.$shippingAddress['street'];
		$delivery_address['postal_code'] = $shippingAddress['postcode'];
		$delivery_address['country']     = $shippingAddress['country_id'];
		$delivery_address['city']		 = $shippingAddress['city'];
 	
 		$delivery_window   = array();
 		$date = new DateTime('NOW');
		$date->setTimeZone(new DateTimeZone('UTC'));
 		$delivery_window['start_time'] = $date->format(DateTime::ATOM);
		$date->add(new DateInterval('PT2H'));
		$delivery_window['end_time'] = $date->format(DateTime::ATOM);
 	
 		$items = $order->getAllVisibleItems();
 		$packages = array();
	    foreach($items as $i):
	    	$package = array();
	    	$package['height'] = 1;
	    	$package['weight'] = $i->getWeight();
	    	$package['length'] = 1;
	    	$package['width'] = 1;
	    	$package['units'] = 1;
	      	$packages[] = $package;
	   	endforeach;

 	
 		$data = array(
 			"api" => $api_key,
 			"name" => $name,
 			"email" => $email,
 			"phone" => $phone,
 			"store_id" => $store_id,
 			"retailer_order_number" => $retailer_order_number,
 			"delivery_window" => $delivery_window,
 			"delivery_address" => $delivery_address,
 			"packages"  => $packages,
 		);
		$mode = Mage::getStoreConfig('paacksettings/settings/test_mode');
		$url = self::PRODUCTION_API;
		if($mode == 1)
			$url = self::TEST_API;
		
 		$resp = $this->CallAPI($url, $data);
 		$json_response = json_decode($resp);
 	}


 	function CallAPI($url, $data = array()){
 		$ch = curl_init();
 		$data_string = json_encode($data);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		    'Content-Type: application/json',                                                                                
		    'Content-Length: ' . strlen($data_string))                                                                       
		);   
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}