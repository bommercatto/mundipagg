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

class Uecommerce_Mundipagg_Block_Info extends Mage_Payment_Block_Info {
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('mundipagg/payment/info/mundipagg.phtml');
	}
	
	/**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
	public function getOrder() {
		return Mage::registry('current_order');
	}
	
	/**
     * Retrieve invoice model instance
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
	public function getInvoice() {
		return Mage::registry('current_invoice');
	}
	
	/**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment() {
    	return Mage::registry('current_shipment');
    }
    
    /**
     * Retrieve payment method
     */
    public function getFormaPagamento() {
    	return $this->getInfo()->getAdditionalInformation('PaymentMethod');
    }
}