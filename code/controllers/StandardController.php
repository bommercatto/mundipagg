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

class Uecommerce_Mundipagg_StandardController extends Mage_Core_Controller_Front_Action {
    /**
     * Order instance
     */
    protected $_order;

    public function getOrder() 
    {
        if ($this->_order == null) {

        }
        return $this->_order;
    }
    
	/**
     * Get block instance
     *
     * @return 
     */
    protected function _getRedirectBlock() 
    {
        return $this->getLayout()->createBlock('standard/redirect');
    }

    public function getStandard() 
    {
        return Mage::getSingleton('mundipagg/standard');
    }

    protected function _expireAjax() 
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Redirect page rendering
     */
    public function redirectAction() 
    {
        switch (Mage::getSingleton('checkout/session')->getApprovalRequestSuccess()) {
            case 'success':
                $this->_redirect('mundipagg/standard/success');
                break;

            case 'partial':
                $this->_redirect('mundipagg/standard/partial');
                break;
            
            case 'cancel':
                $this->_redirect('mundipagg/standard/cancel');
                break;

            default:
                //Render
                $this->loadLayout();
                $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('mundipagg/standard_redirect'));
                $this->renderLayout();
                break;
        }
    }
    
    /**
    * Partial payment
    */
    public function partialAction() 
    {
        $session = Mage::getSingleton('checkout/session');

        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }
        
        $lastQuoteId = $session->getLastSuccessQuoteId();

        $session->setQuoteId($lastQuoteId);

        $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
        
        $this->getOnepage()->setQuote($quote);
        $this->getOnepage()->getQuote()->setIsActive(true);

        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            
            if ($order->getId()) {
                //Render
                $this->loadLayout();
                $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('mundipagg/standard_partial'));
                $this->renderLayout();
            }
            else {
                $this->_redirect();
            }
        }
        else {
            $this->_redirect();
        }
    }

    /**
    * Partial payment Post
    */
    public function partialPostAction()
    {
        $session = Mage::getSingleton('checkout/session');

        // Post
        if($data = $this->getRequest()->getPost('payment', array())) {
            try{
                $lastQuoteId = $session->getLastSuccessQuoteId();

                $session->setQuoteId($lastQuoteId);

                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                
                $this->getOnepage()->setQuote($quote);
                $this->getOnepage()->getQuote()->setIsActive(true);

                // Get Reserved Order Id
                if($reserved_order_id = $this->getOnepage()->getQuote()->getReservedOrderId()) {
                    $order = Mage::getModel('sales/order')->loadByIncrementId($reserved_order_id);

                    if($order->getStatus() == 'pending' OR $order->getStatus() == 'payment_review') {
                        if (empty($data)) {
                            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid data'));
                        }

                        if ($this->getOnepage()->getQuote()->isVirtual()) {
                            $quote->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
                        } else {
                            $quote->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
                        }

                        $payment = $quote->getPayment();
                        $payment->importData($data);

                        $quote->save();

                        $onepage = Mage::getModel('mundipagg/standard');                       
                        $resultPayment = $onepage->doPayment($payment, $order);

                        // We record transaction(s)
                        if(count($resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult) == 1) {
                            $trans = $resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult;
                            
                            $onepage->_addTransaction($order->getPayment(), $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, $trans);
                        }
                        else {
                            foreach($resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult as $key => $trans) {
                                $onepage->_addTransaction($order->getPayment(), $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, $trans);
                            }
                        }

                        // Redirect
                        if(Mage::getSingleton('checkout/session')->getApprovalRequestSuccess() == 'success') {
                            $this->_redirect('mundipagg/standard/success');
                        }
                        else {
                            $this->_redirect('mundipagg/standard/partial');
                        }
                    }
                }
            }
            catch (Exception $e) {
                //Log error
                Mage::logException($e);
            }
        }
        else {
            $this->_redirect();
        }
    }
        		
    /**
     * Cancel page
     */
    public function cancelAction() 
    {
        $session = Mage::getSingleton('checkout/session');

        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        // Set quote as inactive
        Mage::getSingleton('checkout/session')
            ->getQuote()
            ->setIsActive(false)
            ->setTotalsCollectedFlag(false)
            ->save()
            ->collectTotals();

        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        //Render
        $this->loadLayout();
        $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('mundipagg/standard_cancel'));
        $this->renderLayout();

        $session->clear();
    }

    /**
     * Force Cancel page
     */
    public function fcancelAction() 
    {
        $session = Mage::getSingleton('checkout/session');

        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        // Set quote as inactive
        Mage::getSingleton('checkout/session')
            ->getQuote()
            ->setIsActive(false)
            ->setTotalsCollectedFlag(false)
            ->save()
            ->collectTotals();

        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        //Render
        $this->loadLayout();
        $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('mundipagg/standard_fcancel'));
        $this->renderLayout();

        $session->clear();
    }

    /**
     * Success page (also used for Mundipagg return page for payments like "debit" and "boleto")
     */
    public function successAction() 
    {
        $session = Mage::getSingleton('checkout/session');
        $ApprovalRequestSuccess = $session->getApprovalRequestSuccess();
    
		if (!$this->getRequest()->isPost() && $ApprovalRequestSuccess == 'success') {
            if (!$session->getLastSuccessQuoteId()) {
                $this->_redirect('checkout/cart');
                return;
            }

            $session->setQuoteId($session->getMundipaggStandardQuoteId(true));

            // Last Order Id
            $lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		
            // Set quote as inactive
            Mage::getSingleton('checkout/session')
                ->getQuote()
                ->setIsActive(false)
                ->setTotalsCollectedFlag(false)
                ->save()
                ->collectTotals();

            // Load order
            $order = Mage::getModel('sales/order')->load($lastOrderId);
            
            if ($order->getId()) {
                // Render
                $this->loadLayout();
                Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
                $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('mundipagg/standard_success'));
                $this->renderLayout();

                $session->clear();
            }
            else {
            	// Redirect to homepage
            	$this->_redirect('');
            } 
        } 
        else {
            // Get posted data
            $post_data = $this->getRequest()->getPost();

            $api = Mage::getModel('mundipagg/api');

            // Process order
            $api->processOrder($post_data);
        }
    }

    /**
    * Get max number of installments for a value
    */
    public function installmentsAction() 
    {
        $val = $this->getRequest()->getParam('val');

        if(is_numeric($val)) {
            $standard = Mage::getSingleton('mundipagg/standard');

            $valorMinParcelamento = $standard->getConfigData('parcelamento_min');

            // Não ter valor mínimo para parcelar OU Parcelar a partir de um valor mínimo
            if($valorMinParcelamento == 0) {
                $QtdParcelasMax = $standard->getConfigData('parcelamento_max');
            }

            // Parcelar a partir de um valor mínimo
            if($valorMinParcelamento > 0 && $val >= $valorMinParcelamento) {
                $QtdParcelasMax = $standard->getConfigData('parcelamento_max');
            }                                

            // Por faixa de valores
            if($valorMinParcelamento == '') {
                $QtdParcelasMax = $standard->getConfigData('parcelamento_max');
            
                $p = 1;

                for($p = 1; $p <= $QtdParcelasMax; $p++):
                    if($p == 1):
                        $de         = 0;
                        $parcela_de = 0;
                    else:
                        $de         = 'parcelamento_de'.$p;
                        $parcela_de = $standard->getConfigData($de);
                    endif;

                    $ate = 'parcelamento_ate'.$p;
                    $parcela_ate = $standard->getConfigData($ate);
                    
                    if($parcela_de >= 0 && $parcela_ate >= $parcela_de):
                        if($val >= $parcela_de AND $val <= $parcela_ate):
                    
                            $QtdParcelasMax = $p;
                        endif;
                    else:
                        $QtdParcelasMax = $p-1;
                    endif;
                endfor;
            }

            $result['QtdParcelasMax'] = $QtdParcelasMax;

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
}