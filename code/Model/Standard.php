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

class Uecommerce_Mundipagg_Model_Standard extends Mage_Payment_Model_Method_Abstract 
{
    /**
     * Availability options
     */
    protected $_code = 'mundipagg_standard';
    protected $_formBlockType = 'mundipagg/standard_form';
    protected $_infoBlockType = 'mundipagg/info';
    protected $_isGateway = true;
    protected $_canOrder  = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canManageRecurringProfiles = false;
    protected $_allowCurrencyCode = array('BRL', 'USD', 'EUR');
    protected $_isInitializeNeeded = true;

    /**
    * Transaction ID
    **/
    protected $transaction_id = null;
    
    /**
     * Environment (staging / production)
     * @var $environment varchar
     */
    private $environment = null;

    /**
     * Debug mode
     * @var $environment varchar
     */
    private $debug = null;
 
    /**
     * merchantKey na gateway
     * @var $merchantKey varchar
     */
    private $merchantKey = null;
        
    /**
     * instrucoes_caixa na gateway
     * @var $instrucoes_caixa varchar
     */    
    private $instrucoes_caixa;

    /**
     * dias_validade_boleto na gateway
     * @var $dias_validade_boleto int
     */ 
    private $dias_validade_boleto;

    /**
     * CreditCardOperationEnum na gateway
     * @var $CreditCardOperationEnum varchar
     */ 
    private $CreditCardOperationEnum;

    public function getURL() 
    {
        return $this->URL;
    }

    public function setURL($URL) 
    {
        $this->URL = $URL;
    }

    public function getmerchantKey() 
    {
        return $this->merchantKey;
    }

    public function setmerchantKey($merchantKey) 
    {
        $this->merchantKey = $merchantKey;
    }
    
    public function setEnvironment($environment) 
    {
        $this->environment = $environment;
    }

    public function getEnvironment() 
    {
        return $this->environment;
    }

    public function setPaymentMethodCode($PaymentMethodCode) 
    {
        $this->PaymentMethodCode = $PaymentMethodCode;
    }

    public function getPaymentMethodCode() 
    {
        return $this->PaymentMethodCode;
    }

    public function setClearsale($clearsale) 
    {
        $this->clearsale = $clearsale;
    }

    public function getClearsale() 
    {
        return $this->clearsale;
    }

    public function setBankNumber($BankNumber) 
    {
        $this->BankNumber = $BankNumber;
    }

    public function getBankNumber() 
    {
        return $this->BankNumber;
    }

    public function setDebug($debug) 
    {
        $this->debug = $debug;
    }

    public function getDebug() 
    {
        return $this->debug;
    }
        
    public function setDiasValidadeBoleto($DiasValidadeBoleto) 
    {
        $this->dias_validade_boleto = $DiasValidadeBoleto;
    }

    public function getDiasValidadeBoleto() 
    {
        return $this->dias_validade_boleto;
    }

    public function setInstrucoesCaixa($InstrucoesCaixa) 
    {
        $this->instrucoes_caixa = $InstrucoesCaixa;
    }

    public function getInstrucoesCaixa() 
    {
        return $this->instrucoes_caixa;
    }

    public function setCreditCardOperationEnum($CreditCardOperationEnum) 
    {
        $this->CreditCardOperationEnum = $CreditCardOperationEnum;
    }

    public function getCreditCardOperationEnum() 
    {
        return $this->CreditCardOperationEnum;
    }

    public function __construct()
    {
        $this->setEnvironment($this->getConfigData('environment'));

        switch($this->getEnvironment())
        {
            case 'localhost':
            case 'development':
            case 'staging':
            default:
                $this->setmerchantKey(trim($this->getConfigData('merchantKeyStaging')));
                $this->setURL(trim($this->getConfigData('apiUrlStaging')));
                $this->setClearsale($this->getConfigData('clearsale'));
                $this->setPaymentMethodCode(1);
                $this->setBankNumber(341);
                $this->setDiasValidadeBoleto(trim($this->getConfigData('dias_validade_boleto')));
                $this->setInstrucoesCaixa(trim($this->getConfigData('instrucoes_caixa')));
                $this->setDebug($this->getConfigData('debug'));
            break;

            case 'production':
                $this->setmerchantKey(trim($this->getConfigData('merchantKeyProduction')));
                $this->setURL(trim($this->getConfigData('apiUrlProduction')));
                $this->setClearsale($this->getConfigData('clearsale'));
                $this->setDiasValidadeBoleto(trim($this->getConfigData('dias_validade_boleto')));
                $this->setInstrucoesCaixa(trim($this->getConfigData('instrucoes_caixa')));
                $this->setDebug($this->getConfigData('debug'));
            break;
        }
    }

    /**
     * Armazena as informações passadas via formulário no frontend
     * @access public
     * @param array $data
     * @return Uecommerce_Mundipagg_Model_Standard
     */
    public function assignData($data) 
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $mundipagg = array();

        foreach($data->getData() as $id => $value) {
            $mundipagg[$id] = $value;

            //Mage::log($id.' '.$value);

            // We verify if a CPF OR CNPJ is valid
            $posTaxvat = strpos($id, 'taxvat');

            if($posTaxvat !== false && $value != '') {
                if( !$this->validateCPF($value) && !$this->validateCNPJ($value) ){
                    $error = Mage::helper('mundipagg')->__('CPF or CNPJ is invalid');

                    Mage::throwException($error);
                }
            }
        }

        //Set Mundipagg Data in Session
        if(!empty($mundipagg)) {
            $session = Mage::getSingleton('checkout/session');
            $session->setMundipaggData($mundipagg);
            
            $info = $this->getInfoInstance();
            
            if(isset($mundipagg['mundipagg_type'])) {
                $info->setAdditionalInformation('PaymentMethod', $mundipagg['mundipagg_type']);
                
                foreach ($mundipagg as $key => $value) {
                    // We only save current payment method data choosed by user
                    $pos = strpos($key, $mundipagg['mundipagg_type']);

                    // We don't save CcNumber
                    $posCcNumber = strpos($key, 'number');

                    // We don't save Security Code
                    $posCid = strpos($key, 'cid');

                    // We don't save Cc Holder name
                    $posHolderName = strpos($key, 'holder_name');
                    
                    if ($pos !== false && $value != '' && 
                        $posCcNumber === false && 
                        $posCid === false && 
                        $posHolderName === false
                    ) {
                        $info->setAdditionalInformation($key, $value); 
                    }
                }

                // We check if quote grand total is equal to installments sum
                if(strpos($mundipagg['mundipagg_type'], 'CreditCards') !== false && $mundipagg['mundipagg_type'] != '1CreditCards'){
                    $grandTotal = $info->getQuote()->getGrandTotal();
                    $num = substr($mundipagg['mundipagg_type'], 0, 1);

                    $totalInstallmentsToken = 0;
                    $totalInstallmentsNew = 0;

                    for($i=1; $i <= $num; $i++) {
                        if(isset($mundipagg[$num.'CreditCards_token_'.$num.'_'.$i]) && $mundipagg[$num.'CreditCards_token_'.$num.'_'.$i] != 'new') {
                            $value = str_replace(',', '.', $mundipagg[$num.'CreditCards_value_'.$num.'_'.$i]);
                            $totalInstallmentsNew = $totalInstallmentsNew + $value;
                        }
                        else {
                            $value = str_replace(',', '.', $mundipagg[$num.'CreditCards_new_value_'.$num.'_'.$i]);
                            
                            $totalInstallmentsToken = $totalInstallmentsToken + $value;
                        }
                    }

                    // Total Installments from token and Credit Card
                    $totalInstallments = $totalInstallmentsToken + $totalInstallmentsNew;

                    //Mage::log('totalInstallmentsToken: '. $totalInstallmentsToken);
                    //Mage::log('totalInstallmentsNew: '.$totalInstallmentsNew);

                    // If an amount has already been authorized
                    if(Mage::getSingleton('checkout/session')->getAuthorizedAmount()) {
                        $totalInstallments = $totalInstallments + Mage::getSingleton('checkout/session')->getAuthorizedAmount();
                        
                        // Unset session
                        Mage::getSingleton('checkout/session')->setAuthorizedAmount();
                    }

                    //Mage::log('grandTotal: '.$grandTotal);
                    //Mage::log('totalInstallments: '.$totalInstallments);

                    if((string)$grandTotal != (string)$totalInstallments) {
                        Mage::throwException(Mage::helper('payment')->__('Installments does not match with quote.'));
                    }
                }
            }
            else {
                if(isset($mundipagg['payment_method'])) {
                    $info->setAdditionalInformation('PaymentMethod', $mundipagg['payment_method']);
                }
            }
        }
        
        // Get customer_id from Quote (payment made on site) or from POST (payment made from API)
        if(Mage::getSingleton('customer/session')->isLoggedIn()){ 
            if( $this->getQuote()->getCustomer()->getEntityId() ) {
                $customer_id = $this->getQuote()->getCustomer()->getEntityId();
            }
        }
        elseif(isset($mundipagg['entity_id'])) {
            $customer_id = $mundipagg['entity_id'];
        }

        // We verifiy if token is from customer
        if(isset($customer_id) && isset($mundipagg['mundipagg_type'])) {
            $num = substr($mundipagg['mundipagg_type'], 0, 1);

            foreach ($mundipagg as $key => $value) {
                $pos = strpos($key, 'token_'.$num);

                if ($pos !== false && $value != '' && $value != 'new') {
                    $token = Mage::getModel('mundipagg/cardonfile')->load($value);
                
                    if($token->getId() && $token->getEntityId() == $customer_id) {
                        // Ok
                        $info->setAdditionalInformation('CreditCardBrandEnum_'.$key, $token->getCcType());
                    }
                    else {
                        $error = Mage::helper('mundipagg')->__('Token not found');

                        //Log error
                        Mage::log($error);

                        Mage::throwException($error);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Prepare info instance for save
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        if ($this->_canSaveCc) {
            $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        }
        
        $info->setCcNumber(null)
            ->setCcCid(null);

        return $this;
    }

    /**
     * Get payment quote
     */
    public function getPayment() 
    {
        return $this->getQuote()->getPayment();
    }

    /**
     * Get Modulo session namespace
     *
     * @return Uecommerce_Mundipagg_Model_Session
     */
    public function getSession() 
    {
        return Mage::getSingleton('mundipagg/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() 
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() 
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Check order availability
     *
     * @return bool
     */
    public function canOrder() 
    {
        return $this->_canOrder;
    }

    /**
     * Check authorize availability
     *
     * @return bool
     */
    public function canAuthorize() 
    {
        return $this->_canAuthorize;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture() 
    {
        return $this->_canCapture;
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        switch($this->getConfigData('payment_action')){
            case 'order':
                $this->setCreditCardOperationEnum('AuthAndCapture');
            break;

            case 'authorize':
                $this->setCreditCardOperationEnum('AuthOnly');
            break;

            case 'authorize_capture':
                $this->setCreditCardOperationEnum('AuthAndCaptureWithDelay');
            break;
        }
        
        if (version_compare(Mage::getVersion(), '1.5.0', '<')) { 
            $order_action = 'order';
        }
        else {
            $order_action = Mage_Payment_Model_Method_Abstract::ACTION_ORDER;
        }

        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        // If payment method is Boleto Bancário we call "order" method 
        if($payment->getAdditionalInformation('PaymentMethod') == 'BoletoBancario') {
            $this->order($payment, $order->getBaseTotalDue());
            return $this;
        }

        // If it's a multi-payment types we force to ACTION_AUTHORIZE
        $num = substr($payment->getAdditionalInformation('PaymentMethod'), 0, 1);

        if($num > 1) {
            $paymentAction = Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE;
        }

        switch ($paymentAction) {
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
                $payment->authorize(true, $order->getBaseTotalDue());
                break;

            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
                $payment->authorize(true, $order->getBaseTotalDue());
                break;

            case $order_action:
                $this->order($payment, $order->getBaseTotalDue());
                break;

            default:
                $this->order($payment, $order->getBaseTotalDue());
                break;
        }
    }

    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount) 
    {
        try {
            if (!$this->canAuthorize()) {
                Mage::throwException(Mage::helper('payment')->__('Authorize action is not available.'));
            }

            // Load order
            $order = $payment->getOrder();

            // Proceed to authorization on Gateway
            $resultPayment = $this->doPayment($payment, $order);

            // We record transaction(s)
            if(count($resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult) == 1) {
                $trans = $resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult;
                
                $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, $trans);
            }
            else {
                foreach($resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult as $key => $trans) {
                    $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, $trans);
                }
            }

            // Return
            if( isset($resultPayment['error']) ) {
                try {
                    $payment->setSkipOrderProcessing(true);
                    
                    $order->addStatusHistoryComment(Mage::helper('mundipagg')->__($resultPayment['ErrorDescription']));
                    $order->save();

                    $payment->setIsTransactionPending(true);

                    Mage::throwException(Mage::helper('mundipagg')->__($resultPayment['ErrorDescription']));
                } catch (Exception $e) {
                    return $this;
                }
            }
            else {
                // Send new order email when not in admin
                if(Mage::app()->getStore()->getCode() != 'admin') {
                    $order->sendNewOrderEmail();
                }

                if($resultPayment['message'] == 2) {
                    return $this;
                }

                if($resultPayment['message'] == 3) {
                    return $this;
                }
            }

            return $this;
        }
        catch(Exception $e) {
            Mage::log( print_r($e,1) );
        }
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount) 
    {
        if (!$this->canCapture()) {
            Mage::throwException(Mage::helper('payment')->__('Capture action is not available.'));
        }

        if($payment->getAdditionalInformation('PaymentMethod') == 'BoletoBancario') {
            Mage::throwException(Mage::helper('payment')->__('You cannot capture Boleto Bancário.'));
        }

        if($this->getClearsale() == 1) {
            Mage::throwException(Mage::helper('payment')->__('You cannot capture having ClearSale activated.'));
        }

        // Already captured
        if($payment->getAdditionalInformation('CreditCardTransactionStatusEnum') == 'Captured'){
            return $this;
        }

        //Prepare data in order to capture
        if($payment->getAdditionalInformation('OrderKey')) {
            $data['OrderKey']                   = $payment->getAdditionalInformation('OrderKey');
            $data['ManageOrderOperationEnum']   = 'Capture';

            if($payment->getAdditionalInformation('OrderReference')) {
                $data['OrderReference'] = $payment->getAdditionalInformation('OrderReference');
            }
            
            //Call Gateway Api
            $api = Mage::getModel('mundipagg/api');

            $capture = $api->manageOrderRequest($data, $this);

            // We record transaction(s)
            if(isset($capture['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult)) {
                if(count($capture['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult) == 1) {
                    $trans = $capture['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult;
                    
                    $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, $trans);
                }
                else {
                    foreach($capture['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult as $key => $trans) {
                        $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, $trans);
                    }
                }
            }

            if(isset($capture['success']) && $capture['success'] == true) {
                return $this;
            }
            else {
                $error = Mage::helper('mundipagg')->__('Order status is: '. $capture['OrderStatusEnum']);

                //Log error
                Mage::log($error);

                Mage::throwException($error);
            }
        }
        else {
            Mage::throwException(Mage::helper('mundipagg')->__('No OrderKey found.'));
        }
    }

    /**
     * Order payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function order(Varien_Object $payment, $amount) 
    {
        if (!$this->canOrder()) {
            Mage::throwException(Mage::helper('payment')->__('Order action is not available.'));
        }
        
        //Load order
        $order = $payment->getOrder();

        $order = Mage::getModel('sales/order')->loadByIncrementId($order->getRealOrderId());

        // Proceed to payment on Gateway
        $resultPayment = $this->doPayment($payment, $order);

        // Return error
        if( isset($resultPayment['error']) ) {
            try {
                $payment->setSkipOrderProcessing(true);

                $order->addStatusHistoryComment(Mage::helper('mundipagg')->__($resultPayment['ErrorDescription']));
                $order->save();

                Mage::throwException(Mage::helper('mundipagg')->__($resultPayment['ErrorDescription']));
            } 
            catch (Exception $e) {
                return $this;
            }
        }
        else {
            if( isset($resultPayment['message']) ) {
                switch ($resultPayment['message']) {
                    // Boleto
                    case 0:
                        // We record transaction(s)
                        if(count($resultPayment['result']->BoletoTransactionResultCollection->BoletoTransactionResult) == 1) {
                            $trans = $resultPayment['result']->BoletoTransactionResultCollection->BoletoTransactionResult;
                            
                            $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, $trans);
                        }
                        else {
                            foreach($resultPayment['result']->BoletoTransactionResultCollection->BoletoTransactionResult as $key => $trans) {
                                $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, $trans);
                            }
                        }

                        $payment->setTransactionId($this->transaction_id);

                        break;
                    
                    // Credit Card
                    case 1:
                        // We record transaction(s)
                        if(count($resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult) == 1) {
                            $trans = $resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult;
                            
                            $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, $trans);
                        }
                        else {
                            foreach($resultPayment['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult as $key => $trans) {
                                $this->_addTransaction($payment, $trans->TransactionKey, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, $trans);
                            }
                        }

                        // Invoice
                        $order = $payment->getOrder(); 

                        if(!$order->canInvoice()) {
                            //Log error
                            Mage::logException(Mage::helper('core')->__('Cannot create an invoice.'));

                            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                        }

                        // Create invoice
                        $invoice = Mage::getModel('sales/service_order', $payment->getOrder())->prepareInvoice(array());
                        $invoice->register();
                        
                        // Set capture case to online and register the invoice.
                        $invoice->setRequestedCaptureCase('online');
                        $invoice->setTransactionId($this->transaction_id);
                        $invoice->setCanVoidFlag(true);
                        $invoice->getOrder()->setIsInProcess(true);  
                        $invoice->capture();

                        $invoice->save();
                        
                        $payment->setTransactionId($this->transaction_id);

                        break;
                }
            }

            // Send new order email when not in admin
            if(Mage::app()->getStore()->getCode() != 'admin') {
                $order->sendNewOrderEmail();
            }
            
            return $this;
        }
    }

    /**
     * Proceed to payment
     * @param object $order
     */
    public function doPayment($payment, $order) 
    {
        try {
            $session = Mage::getSingleton('checkout/session');
            $MundipaggData = $session->getMundipaggData();
                
            //Post data
            $postData = Mage::app()->getRequest()->getPost();

            //Data to pass to api
            $data['customer_id']                = $order->getCustomerId();
            $data['address_id']                 = $order->getBillingAddress()->getCustomerAddressId();
            $data['payment_method']             = isset($postData['payment']['mundipagg_type'])?$postData['payment']['mundipagg_type']:$MundipaggData['payment_method'];
            
            $type = $data['payment_method'];

            // Payment done with 1 or more Credit Cards
            if(strpos($data['payment_method'], 'CreditCards') !== false) {
                $num = substr($data['payment_method'], 0, 1);

                for($i = 1; $i <= $num; $i++) {
                    // New Credit Card
                    if(
                        !isset($postData['payment'][$num.'CreditCards_token_'.$num.'_'.$i]) || 
                        (isset($postData['payment'][$num.'CreditCards_token_'.$num.'_'.$i]) && $postData['payment'][$num.'CreditCards_token_'.$num.'_'.$i] == 'new') 
                    ) {
                        $data['payment'][$i]['HolderName']         = isset($postData['payment'][$num.'CreditCards_cc_holder_name_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_cc_holder_name_'.$num.'_'.$i] : $MundipaggData[$num.'CreditCards_cc_holder_name_'.$num.'_'.$i];
                        $data['payment'][$i]['CreditCardNumber']   = isset($postData['payment'][$num.'CreditCards_'.$num.'_'.$i.'_cc_number']) ? $postData['payment'][$num.'CreditCards_'.$num.'_'.$i.'_cc_number'] : $MundipaggData[$num.'CreditCards_'.$num.'_'.$i.'_cc_number'];
                        $data['payment'][$i]['ExpMonth']           = isset($postData['payment'][$num.'CreditCards_expirationMonth_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_expirationMonth_'.$num.'_'.$i] : $MundipaggData[$num.'CreditCards_expirationMonth_'.$num.'_'.$i];
                        $data['payment'][$i]['ExpYear']            = isset($postData['payment'][$num.'CreditCards_expirationYear_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_expirationYear_'.$num.'_'.$i] : $MundipaggData[$num.'CreditCards_expirationYear_'.$num.'_'.$i];
                        $data['payment'][$i]['SecurityCode']       = isset($postData['payment'][$num.'CreditCards_cc_cid_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_cc_cid_'.$num.'_'.$i] : $MundipaggData[$num.'CreditCards_cc_cid_'.$num.'_'.$i];
                        $data['payment'][$i]['CreditCardBrandEnum']= $this->issuer(isset($postData['payment'][$num.'CreditCards_'.$num.'_'.$i.'_cc_type']) ? $postData['payment'][$num.'CreditCards_'.$num.'_'.$i.'_cc_type'] : $MundipaggData[$num.'CreditCards_'.$num.'_'.$i.'_cc_type']);
                        $data['payment'][$i]['InstallmentCount']   = isset($postData['payment'][$num.'CreditCards_new_credito_parcelamento_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_new_credito_parcelamento_'.$num.'_'.$i] : 1;
                        $data['payment'][$i]['token']              = isset($postData['payment'][$num.'CreditCards_save_token_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_save_token_'.$num.'_'.$i] : null;
                        
                        if( isset($postData['payment'][$num.'CreditCards_new_value_'.$num.'_'.$i]) && $postData['payment'][$num.'CreditCards_new_value_'.$num.'_'.$i] != '' ) {
                            $data['payment'][$i]['AmountInCents'] = str_replace(',', '.', $postData['payment'][$num.'CreditCards_new_value_'.$num.'_'.$i]);
                            $data['payment'][$i]['AmountInCents'] = $data['payment'][$i]['AmountInCents']*100;
                        }
                        else {
                            if(!isset($postData['partial'])) {
                                $data['payment'][$i]['AmountInCents'] = $order->getGrandTotal()*100;
                            }
                            else { // If partial payment we deduct authorized amount already processed
                                if(Mage::getSingleton('checkout/session')->getAuthorizedAmount()) {
                                    $data['payment'][$i]['AmountInCents'] = $order->getGrandTotal()*100 - Mage::getSingleton('checkout/session')->getAuthorizedAmount()*100;
                                }
                                else {
                                    $data['payment'][$i]['AmountInCents'] = $order->getGrandTotal()*100;
                                }
                            }
                        }

                        $data['payment'][$i]['TaxDocumentNumber']  = isset($postData['payment'][$num.'CreditCards_cc_taxvat_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_cc_taxvat_'.$num.'_'.$i] : $order->getCustomerTaxvat();
                    }
                    else { // Token
                        $data['payment'][$i]['card_on_file_id']    = isset($postData['payment'][$num.'CreditCards_token_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_token_'.$num.'_'.$i] : $MundipaggData[$num.'CreditCards_token_'.$num.'_'.$i];
                        $data['payment'][$i]['InstallmentCount']   = isset($postData['payment'][$num.'CreditCards_credito_parcelamento_'.$num.'_'.$i])?$postData['payment'][$num.'CreditCards_credito_parcelamento_'.$num.'_'.$i]:1;
                        
                        if( isset($postData['payment'][$num.'CreditCards_value_'.$num.'_'.$i]) && $postData['payment'][$num.'CreditCards_value_'.$num.'_'.$i] != '' ) {
                            $data['payment'][$i]['AmountInCents'] = str_replace(',', '.', $postData['payment'][$num.'CreditCards_value_'.$num.'_'.$i]);
                            $data['payment'][$i]['AmountInCents'] = $data['payment'][$i]['AmountInCents']*100;
                        }
                        else {
                            if(!isset($postData['partial'])) {
                                $data['payment'][$i]['AmountInCents'] = $order->getGrandTotal()*100;
                            }
                            else { // If partial payment we deduct authorized amount already processed
                                if(Mage::getSingleton('checkout/session')->getAuthorizedAmount()) {
                                    $data['payment'][$i]['AmountInCents'] = $order->getGrandTotal()*100 - Mage::getSingleton('checkout/session')->getAuthorizedAmount()*100;
                                }
                                else {
                                    $data['payment'][$i]['AmountInCents'] = $order->getGrandTotal()*100;
                                }
                            }
                        }

                        $data['payment'][$i]['TaxDocumentNumber']  = isset($postData['payment'][$num.'CreditCards_cc_taxvat_'.$num.'_'.$i]) ? $postData['payment'][$num.'CreditCards_cc_taxvat_'.$num.'_'.$i] : $order->getCustomerTaxvat();
                    }

                    if($this->validateCPF($data['payment'][$i]['TaxDocumentNumber'])){
                        $data['PersonTypeEnum']      = 'Person';
                        $data['TaxDocumentTypeEnum'] = 'CPF';
                        $data['TaxDocumentNumber']   = $data['payment'][$i]['TaxDocumentNumber'];
                    }
                    
                    // We verify if a CNPJ is informed
                    if($this->validateCNPJ($data['payment'][$i]['TaxDocumentNumber'])){
                        $data['PersonTypeEnum']      = 'Company';
                        $data['TaxDocumentTypeEnum'] = 'CNPJ';
                        $data['TaxDocumentNumber']   = $data['payment'][$i]['TaxDocumentNumber'];
                    }
                }
            }

            // Boleto Payment
            if( $data['payment_method'] == 'BoletoBancario'){
                $data['TaxDocumentNumber']      = isset($postData['payment']['boleto_taxvat'])?$postData['payment']['boleto_taxvat']:$order->getCustomerTaxvat();  
                $data['boleto_parcelamento']    = isset($postData['payment']['boleto_parcelamento'])?$postData['payment']['boleto_parcelamento']:1;
                $data['boleto_dates']           = isset($postData['payment']['boleto_dates'])?$postData['payment']['boleto_dates']:null;
            
                // We verify if a CPF is informed
                if($this->validateCPF($data['TaxDocumentNumber'])){
                    $data['PersonTypeEnum']         = 'Person';
                    $data['TaxDocumentTypeEnum']    = 'CPF';
                }

                // We verify if a CNPJ is informed
                if($this->validateCNPJ($data['TaxDocumentNumber'])){
                    $data['PersonTypeEnum']         = 'Company';
                    $data['TaxDocumentTypeEnum']    = 'CNPJ';
                }
            }

            //Unset MundipaggData data
            $session->setMundipaggData();

            //Api
            $api = Mage::getModel('mundipagg/api');

            //Get approval request from gateway
            switch ($type) {
                case 'BoletoBancario':
                    $approvalRequest = $api->boletoTransaction($order, $data, $this);
                    break;
                
                case $type:
                    if ( in_array($type, $this->getPaymentMethods()) ) {
                        $approvalRequest = $api->creditCardTransaction($order, $data, $this);
                    }
                    else {
                        $approvalRequest['error'] = 'Error';
                        $approvalRequest['ErrorCode'] = 'ErrorCode';
                        $approvalRequest['ErrorDescription'] = 'ErrorDescription';
                    }
                    break;
            }
            
            // Payment gateway error
            if(isset($approvalRequest['error'])) {
                try{ 
                    $info = $this->getInfoInstance();
                    $info->setAdditionalInformation('OrderKey', $approvalRequest['OrderKey']);
                    $info->setAdditionalInformation('OrderReference', $approvalRequest['OrderReference']);
                    $info->save();
                }
                catch(Exception $e) {

                }             

                // Partial payment
                if($approvalRequest['ErrorCode'] == 'multi') {
                    // We set authorized amount in session
                    $orderGrandTotal    = $order->getGrandTotal();
                    $AuthorizedAmount   = 0;

                    foreach($approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult as $key => $result){
                        if($result->Success == true){
                            $AuthorizedAmount = $AuthorizedAmount + ($result->AuthorizedAmountInCents * 0.01);
                        }
                    }

                    //Mage::log('AuthorizedAmount: '.$AuthorizedAmount);
                    //Mage::log('orderGrandTotal: '.$orderGrandTotal);

                    // If authorized amount is the same as order grand total we can show success page
                    if((string)$AuthorizedAmount == (string)$orderGrandTotal) {
                        $payment->setIsTransactionPending(false);
                        
                        $order->setState('new', 'pending', $message = Mage::helper('mundipagg')->__('Amount authorized'));
                        
                        $order->save();

                        Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('success');
                        Mage::getSingleton('checkout/session')->setAuthorizedAmount();
                    }
                    else {
                        Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('partial');
                        Mage::getSingleton('checkout/session')->setAuthorizedAmount($AuthorizedAmount);
                    }

                    Mage::getSingleton('checkout/session')->setCreditCardTransactionResultCollection($approvalRequest['result']);
                }
                else {
                    Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('cancel');

                    $info->setAdditionalInformation('ErrorCode', $approvalRequest['ErrorCode']);
                    $info->setAdditionalInformation('ErrorDescription', $approvalRequest['ErrorDescription']);
                }

                return $approvalRequest;
            }
            else {
                switch($approvalRequest['message']) {
                    // BoletoBancario
                    case 0:
                        Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('success');

                        try{ 
                            $info = $this->getInfoInstance();
                            $info->setAdditionalInformation('OrderKey', $approvalRequest['OrderKey']);
                            $info->setAdditionalInformation('OrderReference', $approvalRequest['OrderReference']);
                            $info->setAdditionalInformation('Boleto', $approvalRequest['Boleto']);
                            $info->save();
                        }
                        catch(Exception $e) {
                            continue;
                        }
                    break;
                    
                    // 1CreditCards
                    case 1: // AuthAndCapture
                    case 2: // AuthOnly
                    case 3: // AuthAndCaptureWithDelay

                        // We set authorized amount in session
                        $orderGrandTotal    = $order->getGrandTotal();
                        $AuthorizedAmount   = 0;

                        if( count($approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult) == 1 ) {
                            $result = $approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult;

                            if($result->Success == true){
                                $AuthorizedAmount = $AuthorizedAmount + ($result->AuthorizedAmountInCents * 0.01);
                            }

                            try{ 
                                $info = $this->getInfoInstance();
                                
                                if(isset($data['payment'][1]['CreditCardNumber'])) {
                                    $info->setAdditionalInformation('CreditCardBrandEnum', $this->check_cc($data['payment'][1]['CreditCardNumber']));
                                    $info->setAdditionalInformation('InstallmentCount', $data['payment'][1]['InstallmentCount']);
                                    $info->save();
                                }
                            }
                            catch(Exception $e) {
                                
                            }
                        }
                        else {
                            foreach($approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult as $key => $result){
                                if($result->Success == true){
                                    $AuthorizedAmount = $AuthorizedAmount + ($result->AuthorizedAmountInCents * 0.01);
                                }
                            }
                        }

                        //Mage::log('AuthorizedAmount: '.$AuthorizedAmount);
                        //Mage::log('orderGrandTotal: '.$orderGrandTotal);

                        // If authorized amount is the same as order grand total we can show success page
                        if((string)$AuthorizedAmount == (string)$orderGrandTotal) {
                            $payment->setIsTransactionPending(false);
                            $order->setState('new', 'pending', $message = 'Amount authorized');
                            $order->save();

                            Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('success');
                            Mage::getSingleton('checkout/session')->setAuthorizedAmount();
                        }
                        else {
                            Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('partial');
                            Mage::getSingleton('checkout/session')->setAuthorizedAmount($AuthorizedAmount);
                        }

                        // Session
                        Mage::getSingleton('checkout/session')->setCreditCardTransactionResultCollection($approvalRequest['result']);
                        
                        // Transaction
                        $TransactionKey = isset($approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult->TransactionKey) ? $approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult->TransactionKey : null;
                        $CreditCardTransactionStatusEnum = isset($approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult->CreditCardTransactionStatusEnum) ? $approvalRequest['result']->CreditCardTransactionResultCollection->CreditCardTransactionResult->CreditCardTransactionStatusEnum : null;
                            
                        try{ 
                            $info = $this->getInfoInstance();

                            if($TransactionKey != null) {
                                $info->setLastTransId($TransactionKey);
                                $this->transaction_id = $TransactionKey;
                            }

                            $info->setAdditionalInformation('OrderKey', $approvalRequest['OrderKey']);
                            $info->setAdditionalInformation('OrderReference', $approvalRequest['OrderReference']);
                
                            if($CreditCardTransactionStatusEnum != null) {
                                $info->setAdditionalInformation('CreditCardTransactionStatusEnum', $CreditCardTransactionStatusEnum);
                            }
                            
                            $info->save();
                        }
                        catch(Exception $e) {
                            continue;
                        }

                    break;
                }

                return $approvalRequest;
            }
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
     * Set capture transaction ID and enable Void to invoice for informational purposes
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processInvoice($invoice, $payment) 
    {
        if($payment->getLastTransId()) {
            $invoice->setTransactionId($payment->getLastTransId());
            $invoice->setCanVoidFlag(true);

            if (Mage::helper('sales')->canSendNewInvoiceEmail($payment->getOrder()->getStoreId())) {
                $invoice->setEmailSent(true);
                $invoice->sendEmail();
            }

            return $this;
        }

        return false;
    }

    /**
     * Check void availability
     *
     * @return bool
     */
    public function canVoid(Varien_Object $payment) 
    {
        if ($payment instanceof Mage_Sales_Model_Order_Creditmemo) {
            return false;
        }
        
        return $this->_canVoid;
    }

    public function void(Varien_Object $payment) 
    {
        if (!$this->canVoid($payment)) {
            Mage::throwException(Mage::helper('payment')->__('Void action is not available.'));
        }

        //Prepare data in order to void
        if($payment->getAdditionalInformation('OrderKey')) {
            $data['OrderKey']                   = $payment->getAdditionalInformation('OrderKey');
            $data['ManageOrderOperationEnum']   = 'Void';
            
            //Call Gateway Api
            $api = Mage::getModel('mundipagg/api');

            $void = $api->manageOrderRequest($data, $this);
            
            if(isset($void['success']) && $void['success'] == true)
            {
                $payment->setAdditionalInformation('OrderStatusEnum', $void['result']->OrderStatusEnum);
                        
                return $this;
            }
            else
            {
                $error = Mage::helper('mundipagg')->__('Order status is: '. $void['result']->OrderStatusEnum);

                //Log error
                Mage::log($error);

                Mage::throwException($error);
            }
        }
        else {
            Mage::throwException(Mage::helper('mundipagg')->__('No OrderKey found.'));
        }
    }

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund() 
    {
        return $this->_canRefund;
    }

    /**
     * Set refund transaction id to payment object for informational purposes
     * Candidate to be deprecated:
     * there can be multiple refunds per payment, thus payment.refund_transaction_id doesn't make big sense
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processBeforeRefund($invoice, $payment) 
    {
        $payment->setRefundTransactionId($invoice->getTransactionId());

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount) 
    {
        if (!$this->canRefund()) {
            Mage::throwException(Mage::helper('payment')->__('Refund action is not available.'));
        }

        //Prepare data in order to refund
        if($payment->getAdditionalInformation('OrderKey')) {
            $data['OrderKey']                   = $payment->getAdditionalInformation('OrderKey');
            $data['ManageOrderOperationEnum']   = 'Void';
            
            //Call Gateway Api
            $api = Mage::getModel('mundipagg/api');

            $refund = $api->manageOrderRequest($data, $this);
            
            if(isset($refund['success']) && $refund['success'] == true)
            {
                $payment->setAdditionalInformation('OrderStatusEnum', $refund['result']->OrderStatusEnum);
                
                return $this;
            }
            else
            {
                $error = Mage::helper('mundipagg')->__('Order status is: '. $refund->OrderStatusEnum);

                //Log error
                Mage::log($error);

                Mage::throwException($error);
            }
        }
        else {
            Mage::throwException(Mage::helper('mundipagg')->__('No OrderKey found.'));
        }
    }

    /**
     * Validate
     */
    public function validate() 
    {
        parent::validate();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        if (!in_array($currency_code, $this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('payment')->__('Selected currency code (' . $currency_code . ') is not compatabile with Mundipagg'));
        }

        $info = $this->getInfoInstance();

        $errorMsg = false;

        if($info->getCcType() != null) {
            $availableTypes = $this->getCcTypes();

            $ccNumber = $info->getCcNumber();

            // remove credit card number delimiters such as "-" and space
            $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
            $info->setCcNumber($ccNumber);
            
            $ccType = '';

            if (in_array($info->getCcType(), $availableTypes)){
                if (!$this->validateCcNum($ccNumber) && $info->getCcType() != 'HI'){
                    $errorMsg = Mage::helper('payment')->__('Invalid Credit Card Number');
                }
            }
            else {
                $errorMsg = Mage::helper('payment')->__('Credit card type is not allowed for this payment method.');
            }

            if (!$info->getCcType()){
                $errorMsg = Mage::helper('payment')->__('Please select your credit card type.');
            }

            if (!$info->getCcOwner()){
                $errorMsg = Mage::helper('payment')->__('Please enter your credit card holder name.');
            }

            if (!$info->getCcCid()){
                $errorMsg = Mage::helper('payment')->__('Please enter a valid credit card verification number.');
            }
            
            if ($ccType != 'SS' && !$this->_validateExpDate('20'.$info->getCcExpYear(), $info->getCcExpMonth())) {
                $errorMsg = Mage::helper('payment')->__('Incorrect credit card expiration date.');
            }       
        }

        if($errorMsg){
            Mage::throwException($errorMsg);
        }

        return $this;
    }
    
    /**
     * Return issuer
     * @param varchar $cardType
     */
    public function issuer($cardType) 
    {
        if( $cardType == '') {
            return '';
        }
        else {
            $issuers = array(
                'VI' => 'Visa',
                'MC' => 'Mastercard',
                'AE' => 'Amex',
                'DI' => 'Diners',
                'HI' => 'Hipercard',
                'EL' => 'Elo',
            );
            
            foreach ($issuers as $key => $issuer) {
                if($key == $cardType) {
                    return $issuer;
                }
            }
        }
        
    }

    /**
     * Prepara para armazenar todas as informações para serem usadas na API
     * @access public
     * @return void
     */
    public function prepare() 
    {
        //Set form fields / config data in session
        $session = Mage::getSingleton('checkout/session');
        $session->setMundipaggFields($this->getStandardCheckoutFormFields());
    }

    /**
     * Redirect Url
     *
     * @return void
     */
    public function getOrderPlaceRedirectUrl() 
    {
        $this->prepare();

        return Mage::getUrl('mundipagg/standard/redirect', array('_secure' => true));
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods() 
    {
        $payment_methods = $this->getConfigData('payment_methods');

        if($payment_methods != '') {
            $payment_methods = explode(",", $payment_methods);
        }
        else {
            $payment_methods = array();
        }

        return $payment_methods;
    }
    
    /**
     * CCards
     */
    public function getCcTypes() 
    {
        $cc_types = $this->getConfigData('cc_types');
        
        if($cc_types != '') {
            $cc_types = explode(",", $cc_types);
        }
        else 
        {
            $cc_types = array();
        }
        
        return $cc_types;
    }

    /**
    * Return Credit Card Type
    */
    public function check_cc($cc) 
    {
        $cards = array(
            "visa"          => "(4\d{12}(?:\d{3})?)",
            "amex"          => "(3[47]\d{13})",
            "jcb"           => "(35[2-8][89]\d\d\d{10})",
            "maestro"       => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
            "solo"          => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
            "mastercard"    => "(5[1-5]\d{14})",
            "switch"        => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
            "diners"        => "(30\d{12})",
            "diners2"       => "(36\d{12})",
            "diners3"       => "(38\d{12})",
            "elo"           => "([0-9]\d{16})",
            "hipercard"     => "([0-9]\d{16})",
        );

        $names = array("Visa", "Amex", "JCB", "Maestro", "Solo", "Mastercard", "Switch", "Diners", "Diners", "Diners", "Elo", "Hipercard");
        $matches = array();
        $pattern = "#^(?:".implode("|", $cards).")$#";

        $result = preg_match($pattern, str_replace(" ", "", $cc), $matches);

        return ($result>0)?$names[sizeof($matches)-2]:'Other';
    }

    protected function _validateExpDate($expYear, $expMonth)
    {
        $date = Mage::app()->getLocale()->date();
        if (!$expYear || !$expMonth || ($date->compareYear($expYear) == 1)
            || ($date->compareYear($expYear) == 0 && ($date->compareMonth($expMonth) == 1))
        ) {
            return false;
        }
        return true;
    }

    /**
     * Validate credit card number
     *
     * @param   string $cc_number
     * @return  bool
     */
    public function validateCcNum($ccNumber)
    {
        $cardNumber = strrev($ccNumber);
        $numSum = 0;

        for ($i=0; $i<strlen($cardNumber); $i++) {
            $currentNum = substr($cardNumber, $i, 1);

            /**
             * Double every second digit
             */
            if ($i % 2 == 1) {
                $currentNum *= 2;
            }

            /**
             * Add digits of 2-digit numbers together
             */
            if ($currentNum > 9) {
                $firstNum = $currentNum % 10;
                $secondNum = ($currentNum - $firstNum) / 10;
                $currentNum = $firstNum + $secondNum;
            }

            $numSum += $currentNum;
        }

        /**
         * If the total has no remainder it's OK
         */
        return ($numSum % 10 == 0);
    }

    /**
    * Validate CPF
    */ 
    public function validateCPF($cpf)
    {   
        // Verifiva se o número digitado contém todos os digitos
        $cpf = preg_replace('[\D]', '', $cpf);
        
        // Verifica se nenhuma das sequências abaixo foi digitada, caso seja, retorna falso
        if (strlen($cpf) != 11 || $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999'){
            return false;
        }
        else {   // Calcula os números para verificar se o CPF é verdadeiro
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }

                $d = ((10 * $d) % 11) % 10;

                if ($cpf{$c} != $d) {
                    return false;
                }
            }

            return true;
        }
    }

    /**
    * Validate CNPJ
    */
    public function validateCNPJ($value)
    { 
        $cnpj = str_replace(array("-"," ","/","."), "", $value);
        $digitos_iguais = 1;

        if (strlen($cnpj) < 14 && strlen($cnpj) < 15) {
            return false;
        }
        for ($i = 0; $i < strlen($cnpj) - 1; $i++) {
 
            if ($cnpj{$i} != $cnpj{$i + 1}) {
                $digitos_iguais = 0;
                break;
            }
        }
        
        if (!$digitos_iguais) {
            $tamanho = strlen($cnpj) - 2;
            $numeros = substr($cnpj, 0, $tamanho);
            $digitos = substr($cnpj, $tamanho);
            $soma = 0;
            $pos = $tamanho - 7;
            for ($i = $tamanho; $i >= 1; $i--) {
                $soma += $numeros{$tamanho - $i} * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $resultado = ($soma % 11 < 2 ? 0 : 11 - $soma % 11);
            if ($resultado != $digitos{0}) {
                return false;
            }
            $tamanho = $tamanho + 1;
            $numeros = substr($cnpj, 0, $tamanho);
            $soma = 0;
            $pos = $tamanho - 7;
            for ($i = $tamanho; $i >= 1; $i--) {
                $soma += $numeros{$tamanho - $i} * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $resultado = ($soma % 11 < 2 ? 0 : 11 - $soma % 11);
            if ($resultado != $digitos{1}) {
                return false;
            } else {
                return true;
            }
        } 
        else {
            return false;
        }
    }

    /**
     * Add payment transaction
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $transactionId
     * @param string $transactionType
     * @param array $transactionAdditionalInfo
     * @return null|Mage_Sales_Model_Order_Payment_Transaction
     */
    public function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType, $transactionAdditionalInfo) 
    {
        $transaction = Mage::getModel('sales/order_payment_transaction');
        $transaction->setOrderPaymentObject($payment);
        
        $transaction = $transaction->loadByTxnId($transactionId);

        $transaction->setOrderPaymentObject($payment);
        $transaction->setTxnType($transactionType);
        $transaction->setTxnId($transactionId);

        if($transactionAdditionalInfo->Success == true && $transactionType == 'authorization') {
            $transaction->setIsClosed(0);
        }
        else {
            $transaction->setIsClosed(1);   
        } 
        
        foreach($transactionAdditionalInfo as $transKey => $value) {
            $transaction->setAdditionalInformation($transKey, $value);
        }

        return $transaction->save(); 
    }
}