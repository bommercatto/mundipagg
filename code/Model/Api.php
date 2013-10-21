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

class Uecommerce_Mundipagg_Model_Api extends Uecommerce_Mundipagg_Model_Standard 
{
	public function __construct()
	{
		parent::_construct();
	}

    /**
     * Credit Card Transaction
     */
	public function creditCardTransaction($Order, $data, Uecommerce_Mundipagg_Model_Standard $standard) 
	{
		try {
			// Installments configuration
			$installment = $standard->getConfigData('parcelamento');
			$QtdParcelasMax = $standard->getConfigData('parcelamento_max');

			// Get Webservice URL
			$url = $standard->getURL();

			// Get store key
			$key = $standard->getMerchantKey();

			// Soap options
			$soap_opt['encoding']   = 'UTF-8';
			$soap_opt['trace']      = true;
			$soap_opt['exceptions'] = true;

			$soap_client = new SoapClient( $url, $soap_opt );

			//Preencho os dados com as informações sobre o pedido
			$_request["createOrderRequest"]["MerchantKey"] 					= $key; 
			$_request["createOrderRequest"]["OrderReference"] 				= $Order->getIncrementId(); // Identificação do pedido na loja

			if($standard->getEnvironment() != 'production') {
				$_request["createOrderRequest"]["OrderReference"] 			= md5(date('Y-m-d H:i:s')); // Identificação do pedido na loja
			}

			// Partial Payment (we use this reference in order to authorize the rest of the amount)
			if($Order->getPayment()->getAdditionalInformation('OrderReference')) {
				$_request["createOrderRequest"]["OrderReference"] = $Order->getPayment()->getAdditionalInformation('OrderReference');
			}

			$baseGrandTotal = str_replace(',', '.', $Order->getBaseGrandTotal());
			$amountInCentsVar = intval(strval(($baseGrandTotal*100)));

			$_request["createOrderRequest"]["AmountInCents"] 				= $amountInCentsVar; // Valor do pedido em centavos	
			$_request["createOrderRequest"]["AmountInCentsToConsiderPaid"] 	= $amountInCentsVar; // Valor do pedido para considerar pago
			$_request["createOrderRequest"]["EmailUpdateToBuyerEnum"] 		= "No"; // Enviar e-mail de atualização do pedido para o comprador: Yes | No | YesIfAuthorized | YesIfNotAuthorized
			$_request["createOrderRequest"]["CurrencyIsoEnum"] 				= "BRL"; //Moeda do pedido

			// CreditCardOperationEnum : if more than one payment method we use AuthOnly and then capture if all are ok
			if(strpos($data['payment_method'], 'CreditCards') !== false) {
				if( intval(substr($data['payment_method'], 0, 1)) > 1 ) {
					$CreditCardOperationEnum = 'AuthOnly';
				}
				else {
					$CreditCardOperationEnum = $standard->getCreditCardOperationEnum();
				}

				foreach ($data['payment'] as $i => $paymentData) {
					// We check if user is not cheating with installments
					if($installment == 1) {
						if($paymentData['InstallmentCount'] > $QtdParcelasMax) {
							$paymentData['InstallmentCount'] = $QtdParcelasMax;
						}
					}
					else {
						$paymentData['InstallmentCount'] = 1;	
					}

					// InstantBuyKey payment
					if(isset($paymentData['card_on_file_id'])) {
						$token = Mage::getModel('mundipagg/cardonfile')->load($paymentData['card_on_file_id']);

						if($token->getId() && $token->getEntityId() == $Order->getCustomerId()) {
							$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["InstantBuyKey"] 				= $token->getToken(); 
							$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["CreditCardBrandEnum"] 		= $token->getCcType();
							$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["CreditCardOperationEnum"] 	= $CreditCardOperationEnum; /** Tipo de operação: AuthOnly | AuthAndCapture | AuthAndCaptureWithDelay  */
							$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["AmountInCents"] 				= intval(strval(($paymentData['AmountInCents']))); // Valor da transação
							$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["InstallmentCount"] 			= $paymentData['InstallmentCount']; // Nº de parcelas
						}
					}
					else { // Credit Card
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["CreditCardNumber"] 			= $paymentData['CreditCardNumber']; // Número do cartão 
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["HolderName"] 				= $paymentData['HolderName']; // Nome do cartão
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["SecurityCode"] 				= $paymentData['SecurityCode']; // Código de segurança
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["ExpMonth"] 					= $paymentData['ExpMonth']; // Mês Exp
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["ExpYear"] 					= $paymentData['ExpYear']; // Ano Exp 
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["CreditCardBrandEnum"] 		= $paymentData['CreditCardBrandEnum']; // Bandeira do cartão : Visa ,MasterCard ,Hipercard ,Amex */
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["CreditCardOperationEnum"] 	= $CreditCardOperationEnum; /** Tipo de operação: AuthOnly | AuthAndCapture | AuthAndCaptureWithDelay  */
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["AmountInCents"] 				= intval(strval(($paymentData['AmountInCents']))); // Valor da transação
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["InstallmentCount"] 			= $paymentData['InstallmentCount']; // Nº de parcelas
					}

					if($standard->getEnvironment() != 'production') {
						$_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$i-1]["PaymentMethodCode"] = $standard->getPaymentMethodCode(); // Código do meio de pagamento 
					}
				}
			}

			// Buyer data
			$_request = $this->buyerData($Order, $data, $_request, $standard);

			// Cart data
			$_request = $this->cartData($Order, $data, $_request, $standard);

			if($standard->getDebug() == 1) {
				$_log_request = $_request;

				foreach($_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"] as $key => $paymentData) {
					if(isset($_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["CreditCardNumber"])) {
						$_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["CreditCardNumber"] = 'xxxxxxxxxxxxxxxx';
					}

					if(isset($_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["SecurityCode"])){
						$_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["SecurityCode"] = 'xxx';
					}

					if(isset($_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["ExpMonth"])){
						$_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["ExpMonth"] = 'xx';
					}

					if(isset($_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["ExpYear"])){
						$_log_request["createOrderRequest"]["CreditCardTransactionCollection"]["CreditCardTransaction"][$key]["ExpYear"] = 'xx';
					}
				}

				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion());
				Mage::log(print_r($_log_request,1));
			}

			// Envia os dados para o serviço da MundiPagg
			$_response = $soap_client->CreateOrder($_request);

			if($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion());
				Mage::log(print_r($_response,1));
			}

			// Is there an error?
			if($_response->CreateOrderResult->ErrorReport != null){
				$_errorItemCollection = $_response->CreateOrderResult->ErrorReport->ErrorItemCollection;

				// Return errors
				return array(
					'error' 			  => 1, 
					'ErrorItemCollection' => $_errorItemCollection, 
				);
			}
			
			// Transactions colllection
			$creditCardTransactionResultCollection = $_response->CreateOrderResult->CreditCardTransactionResultCollection;

			// Only 1 transaction
			if( count($creditCardTransactionResultCollection->CreditCardTransactionResult) == 1 ) {
				if($_response->CreateOrderResult->Success == true) {
					$trans = $creditCardTransactionResultCollection->CreditCardTransactionResult;

					// We save Card On File
					if( $data['customer_id'] != 0 && isset($data['payment'][1]['token']) && $data['payment'][1]['token'] == 'new' ) {
						$cardonfile = Mage::getModel('mundipagg/cardonfile');

						$cardonfile->setEntityId($data['customer_id']);
						$cardonfile->setAddressId($data['address_id']);
						$cardonfile->setCcType($data['payment'][1]['CreditCardBrandEnum']);
						$cardonfile->setCreditCardMask($trans->CreditCardNumber);
						$cardonfile->setExpiresAt(date("Y-m-t", mktime(0, 0, 0, $data['payment'][1]['ExpMonth'], 1, $data['payment'][1]['ExpYear'])));
						$cardonfile->setToken($trans->InstantBuyKey);
						$cardonfile->setActive(1);

						$cardonfile->save();
					}

					return array(
						'success' 		=> true, 
						'message'		=> 1,
						'returnMessage'	=> $creditCardTransactionResultCollection->CreditCardTransactionResult->AcquirerMessage, 
						'OrderKey'		=> $_response->CreateOrderResult->OrderKey,
						'OrderReference'=> $_response->CreateOrderResult->OrderReference,
						'result'		=> $_response->CreateOrderResult,
					);
				}
				else {
					return array(
						'error' 			=> 1, 
						'ErrorCode'			=> $creditCardTransactionResultCollection->CreditCardTransactionResult->AcquirerReturnCode, 
						'ErrorDescription' 	=> $creditCardTransactionResultCollection->CreditCardTransactionResult->AcquirerMessage, 
						'OrderKey'			=> $_response->CreateOrderResult->OrderKey,
						'OrderReference'	=> $_response->CreateOrderResult->OrderReference,
						'result'			=> $_response->CreateOrderResult,
					);
				}
			}
			else { // More than 1 transaction
				$allTransactions = $creditCardTransactionResultCollection->CreditCardTransactionResult;

				// We remove other transactions made before
				$actualTransactions 	= count($data['payment']);
				$totalTransactions 		= count($creditCardTransactionResultCollection->CreditCardTransactionResult);
				$transactionsToDelete 	= $totalTransactions - $actualTransactions;
				
				if($totalTransactions > $actualTransactions) {
					for($i=0;$i<=($transactionsToDelete-1);$i++) {
						unset($allTransactions[$i]);
					}

					// Reorganize array indexes from 0
					$allTransactions = array_values($allTransactions);
				}

				// We save Cards On File for current transaction(s)
				foreach ($allTransactions as $key => $trans) {
					if( $data['customer_id'] != 0 && isset($data['payment'][$key+1]['token']) && $data['payment'][$key+1]['token'] == 'new' ) {
						$cardonfile = Mage::getModel('mundipagg/cardonfile');

						$cardonfile->setEntityId($data['customer_id']);
						$cardonfile->setAddressId($data['address_id']);
						$cardonfile->setCcType($data['payment'][$key+1]['CreditCardBrandEnum']);
						$cardonfile->setCreditCardMask($trans->CreditCardNumber);
						$cardonfile->setExpiresAt(date("Y-m-t", mktime(0, 0, 0, $data['payment'][$key+1]['ExpMonth'], 1, $data['payment'][$key+1]['ExpYear'])));
						$cardonfile->setToken($trans->InstantBuyKey);
						$cardonfile->setActive(1);

						$cardonfile->save();
					}
				}

				// Order is a success
				if($_response->CreateOrderResult->Success == true) {
					return array(
						'success' 		=> true, 
						'message'		=> 1,
						'OrderKey'		=> $_response->CreateOrderResult->OrderKey,
						'OrderReference'=> $_response->CreateOrderResult->OrderReference,
						'result'		=> $_response->CreateOrderResult,
					);
				}
				else {
					return array(
						'error' 			=> 1,
						'ErrorCode'			=> 'multi', 
						'ErrorDescription'	=> 'Check transactions tab on the left for more details', 
						'OrderKey'			=> $_response->CreateOrderResult->OrderKey,
						'OrderReference'	=> $_response->CreateOrderResult->OrderReference,
						'result'			=> $_response->CreateOrderResult,
					);
				}	
			}
		} 
		catch (Exception $e) {
			//Redirect to Cancel page
			Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('cancel');
			
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			// Return error
			$approvalRequest['error'] = 'Error WS';
            $approvalRequest['ErrorCode'] = 'ErrorCode WS';
            $approvalRequest['ErrorDescription'] = 'ErrorDescription WS';

            return $approvalRequest;
		}
	}

	/**
	* Boleto transaction
	**/
	public function boletoTransaction($Order, $data, Uecommerce_Mundipagg_Model_Standard $standard) 
	{
		try {
			// Get Webservice URL
			$url = $standard->getURL();

			// Get store key
			$key = $standard->getMerchantKey();

			// Soap options
			$soap_opt['encoding']   = 'UTF-8';
			$soap_opt['trace']      = true;
			$soap_opt['exceptions'] = true;

			$soap_client = new SoapClient( $url, $soap_opt );

			// Set Data
			$_request["createOrderRequest"]["CurrencyIsoEnum"] 	= 'BRL';
			$_request["createOrderRequest"]["MerchantKey"] 		= $key; 
			$_request["createOrderRequest"]["OrderReference"] 	= $Order->getIncrementId();

			if($standard->getEnvironment() != 'production') {
				$_request["createOrderRequest"]["OrderReference"] = md5(date('Y-m-d H:i:s')); // Identificação do pedido na loja
			}

			for($i=1;$i<=$data['boleto_parcelamento'];$i++) {
				if(!empty($data['boleto_dates'])) {
					$date_pagamento_boleto 				= $data['boleto_dates'][$i-1];
					$now 								= strtotime(date('Y-m-d'));
				    $your_date 							= strtotime($date_pagamento_boleto);
				    $datediff 							= $your_date - $now;
				    $DaysToAddInBoletoExpirationDate 	= floor($datediff/(60*60*24));
				}
				else {
					$DaysToAddInBoletoExpirationDate 	= $standard->getDiasValidadeBoleto();
				}

				$baseGrandTotal = str_replace(',', '.', $Order->getBaseGrandTotal());
				$amountInCentsVar = intval(strval((($baseGrandTotal/$data['boleto_parcelamento'])*100)));

				$_request["createOrderRequest"]["BoletoTransactionCollection"]["BoletoTransaction"][$i-1]['AmountInCents'] 					= $amountInCentsVar;
				$_request["createOrderRequest"]["BoletoTransactionCollection"]["BoletoTransaction"][$i-1]['Instructions'] 					= $standard->getInstrucoesCaixa();
				$_request["createOrderRequest"]["BoletoTransactionCollection"]["BoletoTransaction"][$i-1]['DaysToAddInBoletoExpirationDate']= $DaysToAddInBoletoExpirationDate;
				
				if($standard->getEnvironment() != 'production') {
					$_request["createOrderRequest"]["BoletoTransactionCollection"]["BoletoTransaction"][$i-1]['BankNumber'] 				= $standard->getBankNumber();
				}
			}
			
			// Buyer data
			$_request = $this->buyerData($Order, $data, $_request, $standard);

			// Cart data
			$_request = $this->cartData($Order, $data, $_request, $standard);

			if($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion());
				Mage::log(print_r($_request,1));
			}
			
			// Envia os dados para o serviço da MundiPagg
			$_response = $soap_client->CreateOrder($_request);

			if($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion());
				Mage::log(print_r($_response,1));
			}

			// Is there an error?
			if($_response->CreateOrderResult->ErrorReport != null){
				$_errorItemCollection = $_response->CreateOrderResult->ErrorReport->ErrorItemCollection;
				
				foreach($_errorItemCollection as $errorItem){
					$errorCode 			= $errorItem->ErrorCode;
					$ErrorDescription 	= $errorItem->Description;
				}
				
				return array(
					'error' 			=> 1, 
					'ErrorCode' 		=> $errorCode, 
					'ErrorDescription' 	=> Mage::helper('mundipagg')->__($ErrorDescription),
				);
			}

			// Success
			if(count($_response->CreateOrderResult->BoletoTransactionResultCollection->BoletoTransactionResult)>1) {
				$returnBoleto = $_response->CreateOrderResult->BoletoTransactionResultCollection->BoletoTransactionResult;
			}
			else {
				$boleto = $_response->CreateOrderResult->BoletoTransactionResultCollection->BoletoTransactionResult;
				$returnBoleto[0] = $boleto;
			}

			// Transactions results
			return array(
				'success' 		=> true, 
				'message' 		=> 0, 
				'OrderKey'		=> $_response->CreateOrderResult->OrderKey,
				'OrderReference'=> $_response->CreateOrderResult->OrderReference,
				'Boleto'		=> $returnBoleto,
				'result'		=> $_response->CreateOrderResult,
			);
		}
		catch (Exception $e) {
			//Redirect to Cancel page
			Mage::getSingleton('checkout/session')->setApprovalRequestSuccess('cancel');
			
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			// Return error
			$approvalRequest['error'] = 'Error WS';
            $approvalRequest['ErrorCode'] = 'ErrorCode WS';
            $approvalRequest['ErrorDescription'] = 'ErrorDescription WS';

            return $approvalRequest;
		}
	}

	/**
	* Set buyer data
	*/
	public function buyerData($Order, $data, $_request, $standard) 
	{
		$billingAddress = $Order->getBillingAddress();
		$street = $billingAddress->getStreet();
		$region_code = $billingAddress->getRegionCode();

		if($billingAddress->getRegionCode() == '') {
			$region_code = 'RJ';
		}

		$telephone = preg_replace('[\D]', '', $billingAddress->getTelephone());

		if($billingAddress->getTelephone() == '') {
			$telephone = '55(21)88888888';
		}

		// In case we doesn't have CPF or CNPJ informed we set default value for MundiPagg (required field)
		$data['TaxDocumentNumber'] = isset($data['TaxDocumentNumber']) ? $data['TaxDocumentNumber'] : $Order->getCustomerTaxvat();

		$invalid = 0;

		if($this->validateCPF($data['TaxDocumentNumber'])){
            $data['PersonTypeEnum']      = 'Person';
            $data['TaxDocumentTypeEnum'] = 'CPF';
            $data['TaxDocumentNumber']   = $data['TaxDocumentNumber'];
        }
        else {
        	$invalid++;
        }
        
        // We verify if a CNPJ is informed
        if($this->validateCNPJ($data['TaxDocumentNumber'])){
            $data['PersonTypeEnum']      = 'Company';
            $data['TaxDocumentTypeEnum'] = 'CNPJ';
            $data['TaxDocumentNumber']   = $data['TaxDocumentNumber'];
        }
        else {
        	$invalid++;
        }

		if($invalid == 2) {
			$data['TaxDocumentNumber'] 		= '00000000000';
			$data['TaxDocumentTypeEnum'] 	= 'CPF';
			$data['PersonTypeEnum'] 		= 'Person';
		}

		// Remove all other characters than decimals from TaxDocumentNumber
		$data['TaxDocumentNumber'] = preg_replace('[\D]', '', $data['TaxDocumentNumber']);

		// Request
		$_request["createOrderRequest"]["Buyer"]["TaxDocumentNumber"] 	= $data['TaxDocumentNumber'];
		$_request["createOrderRequest"]["Buyer"]["Name"] 				= $Order->getCustomerName();
		$_request["createOrderRequest"]["Buyer"]["GenderEnum"] 			= 'M';
		$_request["createOrderRequest"]["Buyer"]["Email"] 				= $Order->getCustomerEmail();
		$_request["createOrderRequest"]["Buyer"]["PersonTypeEnum"] 		= $data['PersonTypeEnum'];
		$_request["createOrderRequest"]["Buyer"]["TaxDocumentTypeEnum"] = $data['TaxDocumentTypeEnum'];
		$_request["createOrderRequest"]["Buyer"]["HomePhone"] 			= $telephone;
		
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["AddressTypeEnum"] 	= 'Billing';
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["City"] 				= $billingAddress->getCity();
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["District"] 			= isset($street[2])?$street[2]:'xxx';
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["Number"] 			= isset($street[1])?$street[1]:'0';
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["State"] 			= $region_code;
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["Street"] 			= isset($street[0])?$street[0]:'xxx';
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["ZipCode"] 			= preg_replace('[\D]', '', $billingAddress->getPostcode());
		$_request["createOrderRequest"]["Buyer"]["BuyerAddressCollection"]["BuyerAddress"]["CountryEnum"] 		= 'Brazil';

		return $_request;
	}

	/**
	* Set cart data
	*/
	public function cartData($Order, $data, $_request, $standard)
	{
		$productIds = array();

		foreach ($Order->getItemsCollection() as $item) {
			if($item->getBasePrice() > 0) {
				$products[$item->getProductId()]['sku'] 	= $item->getProductId();
				$products[$item->getProductId()]['name'] 	= $item->getName();
	            $products[$item->getProductId()]['qty'] 	= round($item->getQtyOrdered(),0);
	            $products[$item->getProductId()]['price'] 	= $item->getBasePrice();
        	}
        }

        $i = 0;

        $_request["createOrderRequest"]["ShoppingCartCollection"]["ShoppingCart"]["FreightCostInCents"] = $Order->getBaseShippingInclTax()*100;

        foreach ($products as $productId) {
			if($standard->getConfigData('clearsale') == 1) {
        		$_request["createOrderRequest"]["ShoppingCartCollection"]["ShoppingCart"]["ShoppingCartItemCollection"][$i]["ItemReference"] 	= $productId['sku'];
        		$_request["createOrderRequest"]["ShoppingCartCollection"]["ShoppingCart"]["ShoppingCartItemCollection"][$i]["Name"] 			= $productId['name'];
            	$_request["createOrderRequest"]["ShoppingCartCollection"]["ShoppingCart"]["ShoppingCartItemCollection"][$i]["Quantity"] 		= $productId['qty'];
        		$_request["createOrderRequest"]["ShoppingCartCollection"]["ShoppingCart"]["ShoppingCartItemCollection"][$i]["UnitCostInCents"] 	= $productId['price']*100;
        	}

        	$_request["createOrderRequest"]["ShoppingCartCollection"]["ShoppingCart"]["ShoppingCartItemCollection"][$i]["TotalCostInCents"] = $productId['qty']*$productId['price']*100;

            $i++;
        }

		return $_request;
	}

	/**
	* Manage Order Request: capture / void / refund
	**/
	public function manageOrderRequest($data, Uecommerce_Mundipagg_Model_Standard $standard) 
	{
		try {
			if($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion());
				Mage::log(print_r($data,1));
			}

			// Get Webservice URL
			$url = $standard->getURL();

			// Get store key
			$key = $standard->getMerchantKey();

			// Soap options
			$soap_opt['encoding']   = 'UTF-8';
			$soap_opt['trace']      = true;
			$soap_opt['exceptions'] = true;

			$soap_client = new SoapClient( $url, $soap_opt );

			$_requestManageOrder["manageOrderRequest"]["MerchantKey"] 	= $key;
			$_requestManageOrder["manageOrderRequest"]["OrderKey"]		= $data['OrderKey'];

			if(isset($data['OrderReference'])) {
				$_requestManageOrder["manageOrderRequest"]["OrderReference"] = $data['OrderReference'];
			}

			$_requestManageOrder["manageOrderRequest"]["ManageOrderOperationEnum"] = $data['ManageOrderOperationEnum'];

			if($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion());
				Mage::log(print_r($_requestManageOrder,1));
			}

			//Envio os dados para o serviço para realizar a captura
			$_responseManageOrder = $soap_client->ManageOrder($_requestManageOrder);

			if($standard->getDebug() == 1) {
				Mage::log('Uecommerce_Mundipagg: '. Mage::helper('mundipagg')->getExtensionVersion());
				Mage::log(print_r($_responseManageOrder,1));
			}

			// Return
			$return = array();
			
			// Credit Card Transactions colllection
			$creditCardTransactionResultCollection = $_responseManageOrder->ManageOrderResult->CreditCardTransactionResultCollection;
			
			// Return
			if($_responseManageOrder->ManageOrderResult->Success == true) {
				return array(
					'success' 	=> true, 
					'result'	=> $_responseManageOrder->ManageOrderResult,
				);
			}
			else {
				return array(
					'success' 			=> false, 
					'error'				=> true,
					'result'			=> $_responseManageOrder->ManageOrderResult,
					'OrderStatusEnum'	=> $_responseManageOrder->ManageOrderResult->OrderStatusEnum,
				);
			}
		}
		catch (Exception $e) {
			//Redirect to Cancel page
			Mage::getSingleton('checkout/session')->setApprovalRequestSuccess(false);
			
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			// Throw Exception
			Mage::throwException(Mage::helper('mundipagg')->__('Payment Error'));
		}
	}

	/**
     * Process order
     * @param $order
     * @param $data
     */
	public function processOrder($post_data) 
	{
		try {
			if(isset($post_data['xmlStatusNotification'])) {
				$xmlStatusNotificationString 	= htmlspecialchars_decode($post_data['xmlStatusNotification']);
				$xml 							= simplexml_load_string($xmlStatusNotificationString);
				$json 							= json_encode($xml);
				$data 							= json_decode($json, true);

				$OrderReference = $data['OrderReference'];
				
				if(!empty($data['BoletoTransaction'])) {
					$status 				= $data['BoletoTransaction']['BoletoTransactionStatus'];
					$TransactionKey 		= $data['BoletoTransaction']['TransactionKey'];
					$CapturedAmountInCents 	= $data['BoletoTransaction']['AmountPaidInCents'];
				}

				if(!empty($data['CreditCardTransaction'])) {
					$status 				= $data['CreditCardTransaction']['CreditCardTransactionStatus'];
					$TransactionKey 		= $data['CreditCardTransaction']['TransactionKey'];
					$CapturedAmountInCents 	= $data['CreditCardTransaction']['CapturedAmountInCents'];
				}

				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($OrderReference);

				if(!$order->getId()) {
					echo 'KO';
					exit;
				}

				switch($status){
	                case 'Captured':
	                case 'Paid':
	                case 'OverPaid':
	                case 'Overpaid':

						if ($order->canUnhold()) {
							$order->unhold();
						}

						if(!$order->canInvoice()) {
							$order->addStatusHistoryComment('Cannot create an invoice.', false);
							$order->save();
							echo 'KO';
							exit;
						}
						
						// Create invoice
						if($order->canInvoice()) {
							$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
							
							if (!$invoice->getTotalQty()) {
								$order->addStatusHistoryComment('Cannot create an invoice without products.', false);
								$order->save();
								echo 'KO';
								exit;
							}

							$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
							$invoice->register();
							$invoice->sendEmail(true);
					    	$invoice->setEmailSent(true);
					   		$invoice->getOrder()->setCustomerNoteNotify(true); 
							$invoice->getOrder()->setIsInProcess(true);
							$invoice->setTransactionId($TransactionKey);
							$invoice->setCanVoidFlag(true);

							$transactionSave = Mage::getModel('core/resource_transaction')
								->addObject($invoice)
								->addObject($invoice->getOrder());
							$transactionSave->save();

							$order->addStatusHistoryComment('Captured offline amount of R$'.$CapturedAmountInCents*0.01, false);
							
							$payment = $order->getPayment();

							if($payment->getAdditionalInformation('PaymentMethod') == '1CreditCards') {
								$payment->setAdditionalInformation('CreditCardTransactionStatusEnum', $data['CreditCardTransaction']['CreditCardTransactionStatus']);
								$payment->save();
							}

							if($payment->getAdditionalInformation('PaymentMethod') == 'BoletoBancario') {
								$payment->setAdditionalInformation('CreditCardTransactionStatusEnum', $data['BoletoTransaction']['BoletoTransactionStatus']);
								$payment->save();
							}

							if($status == 'OverPaid' ||  $status == 'Overpaid') {
								$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'overpaid');
							}
							else {
								$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
							}	

							$order->save();

							echo 'OK';
					    	exit;
						}

	                    break;

	                case 'UnderPaid':
	                case 'Underpaid':

	                	if ($order->canUnhold()) {
							$order->unhold();
						}

	                	$order->addStatusHistoryComment('Captured offline amount of R$'.$CapturedAmountInCents*0.01, false);
						$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'underpaid');
						$order->save();

						echo 'OK';
					    exit;

	                	break;

	                case 'Canceled':
	                 		if(!$order->canCancel()) {
	                 			$order->addStatusHistoryComment('Cannot cancel order.', false);
								$order->save();

								echo 'KO';
								exit;
	                 		}

	                    	if ($order->canCancel()) {
								$order->cancel();
								$order->addStatusHistoryComment('Order canceled.', false);
								$order->save();

								echo 'OK';
								exit;
							}
	                    break;

	                // For other status we add comment to history
	                default:
	                	$order->addStatusHistoryComment($status, false);
						$order->save();

						echo 'OK';
						exit;
	                 	break;
	            } 
            }
            else {
            	echo 'KO';
            	exit;
            } 
		} 
		catch (Exception $e) {
			//Log error
			Mage::logException($e);

			//Mail error
			$this->mailError(print_r($e->getMessage(), 1));

			echo 'KO';
			exit;
		}
    }

	/**
	 * Mail error to Mage::getStoreConfig('trans_email/ident_custom1/email')
	 * @param string $message
	 */
	public function mailError($message = '') 
	{
		//Send email
		$mail = Mage::getModel('core/email');
	    $mail->setToName(Mage::getStoreConfig('trans_email/ident_custom1/name'));
	    $mail->setToEmail(Mage::getStoreConfig('trans_email/ident_custom1/email'));
	    $mail->setBody($message);
	    $mail->setSubject('=?utf-8?B?'.base64_encode(Mage::getStoreConfig('system/store/name').' - erro').'?=');
	    $mail->setFromEmail(Mage::getStoreConfig('trans_email/ident_sales/email'));
	    $mail->setFromName(Mage::getStoreConfig('trans_email/ident_sales/name'));
	    $mail->setType('html');
			
		$mail->send();
	}
}