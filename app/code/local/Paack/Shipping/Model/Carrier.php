<?php

class Paack_Shipping_Model_Carrier extends Mage_Shipping_Model_Carrier_Abstract
  implements Mage_Shipping_Model_Carrier_Interface{

  protected $_code = 'paack_shipping';

  public function collectRates(Mage_Shipping_Model_Rate_Request $request) {

    $result = Mage::getModel('shipping/rate_result');

    $eligibleForDelivery = false;

    $file = Mage::getModuleDir('Model', 'Paack_Shipping')."/Model/Data/postal.csv";
    $data = file_get_contents($file);
    $postal_codes = explode(PHP_EOL, $data);


    foreach ($postal_codes as $postal_code) {
      if(trim($postal_code) == $request->getDestPostcode()){
        $eligibleForDelivery = true;
      }
    }

    if($eligibleForDelivery){
      $result->append($this->_getStandardShippingRate());
    }

    return $result;
  }

  protected function _getStandardShippingRate() {
    $rate = Mage::getModel('shipping/rate_result_method');
    $rate->setCarrier($this->_code);
    $rate->setCarrierTitle($this->getConfigData('title'));
    // $rate->setMethod('Paack');
    $rate->setMethodTitle('Standard');

    $rate->setPrice($this->getConfigData('cost'));
    $rate->setCost(0);

    return $rate;
  }

  public function getAllowedMethods() {
    return array(
      'standard' => 'Standard'
    );
  }

}
