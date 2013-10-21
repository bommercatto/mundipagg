<?php
/**
 * Uecommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Uecommerce EULA.
 * It is also available through the world-wide-web at this URL:
 * http://www.uecommerce.com.br/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.uecommerce.com.br/ for more information
 *
 * @category   Uecommerce
 * @package    Uecommerce_Mundipagg
 * @copyright  Copyright (c) 2012 Uecommerce (http://www.uecommerce.com.br/)
 * @license    http://www.uecommerce.com.br/
 */

/**
 * Mundipagg Payment module
 *
 * @category   Uecommerce
 * @package    Uecommerce_Mundipagg
 * @author     Uecommerce Dev Team
 */

class Uecommerce_Mundipagg_Model_Observer extends Uecommerce_Mundipagg_Model_Standard
{
	/*
     * Update status and notify customer
     */
    private function updateStatus($order, $state, $status, $comment, $notified) { 
	    try {
    		$order->setState($state, true, $comment, $notified);
	        $order->save();
    	} 
    	catch (Exception $e) {
    		//Api
	        $api = Mage::getModel('mundipagg/api');
	        
			//Log error
			Mage::logException($e);

			//Mail error
			$api->mailError(print_r($e->getMessage(), 1));
		}
    }

    /**
    * Set On Hold status for orders paid with Boleto
    **/
    public function onHoldOrder($event){
    	$method = $event->getOrder()->getPayment()->getAdditionalInformation('PaymentMethod');
    	
    	if($method == 'BoletoBancario') {
    		$comment = Mage::helper('mundipagg')->__('Waiting for Boleto Bancário payment');
                        
    		$this->updateStatus($event->getOrder(), Mage_Sales_Model_Order::STATE_HOLDED, true, $comment, false);
    	}
    }
}