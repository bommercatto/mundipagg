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

class Uecommerce_Mundipagg_Block_Standard_Partial extends Mage_Core_Block_Template {
	/**
     * Internal constructor
     * Set template for redirect
     *
     */
	public function __construct() 
	{
		parent::_construct();
        $this->setTemplate('mundipagg/partial.phtml');

        // Get Customer Credit Cards Saved On File
        if($this->helper('customer')->isLoggedIn())
        {
            $entity_id = Mage::getSingleton('customer/session')->getId();

            $CcsCollection = Mage::getResourceModel('mundipagg/cardonfile_collection')
                ->addEntityIdFilter($entity_id)
                ->addExpiresAtFilter();

            $this->setCcs($CcsCollection);
        }
        else 
        {
            $this->setCcs(array());
        }
    }

    /**
     * Initialize data and prepare it for output
     */
    protected function _beforeToHtml()
    {
        return parent::_beforeToHtml();
    }

    /**
     * Return Standard model
     */
    public function getStandard() 
    {
        return Mage::getModel('mundipagg/standard');
    }

    public function getOrder()
    {
        $session = Mage::getSingleton('checkout/session');

        $session->setQuoteId($session->getMundipaggStandardQuoteId(true));

        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            return $order;
        }
    }
}