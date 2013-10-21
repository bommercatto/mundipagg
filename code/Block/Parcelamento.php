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

class Uecommerce_Mundipagg_Block_Parcelamento extends Mage_Core_Block_Template {
	protected $price = null;

	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('mundipagg/parcelamento.phtml');
	}

	protected function _beforeToHtml()
	{
		$this->setPrice($this->getData('price'));
	}

	public function setPrice($price)
	{
		$this->price = $price;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function getParcelamento()
	{
		$active = Mage::getStoreConfig('payment/mundipagg_standard/active');

		if($active == true) {
			$parcelamento = Mage::getStoreConfig('payment/mundipagg_standard/parcelamento');
			$parcelamento_min = Mage::getStoreConfig('payment/mundipagg_standard/parcelamento_min');
			$parcelamento_max = Mage::getStoreConfig('payment/mundipagg_standard/parcelamento_max');

			$valorMinParcelamento = $parcelamento_min;

			// Não ter valor mínimo para parcelar OU Parcelar a partir de um valor mínimo
			if($valorMinParcelamento == 0) {
				$QtdParcelasMax = $parcelamento_max;
			}

			// Parcelar a partir de um valor mínimo
			if($valorMinParcelamento > 0 && $this->getPrice() >= $valorMinParcelamento) {
				$QtdParcelasMax = $parcelamento_max;
			}	                  	              	

			// Por faixa de valores
			if($valorMinParcelamento == '') {
				$QtdParcelasMax = $parcelamento_max;

				$p = 1;

				for($p = 1; $p <= $QtdParcelasMax; $p++) {
					if($p == 1) {
						$de 		= 0;
						$parcela_de = 0;
					}
					else {
						$de 		= 'parcelamento_de'.$p;
						$parcela_de = Mage::getStoreConfig('payment/mundipagg_standard/'.$de);
					}

					$ate 			= 'parcelamento_ate'.$p;
					$parcela_ate= Mage::getStoreConfig('payment/mundipagg_standard/'.$ate);
					
					if($parcela_de >= 0 && $parcela_ate >= $parcela_de) {
						if($this->getPrice() >= $parcela_de AND $this->getPrice() <= $parcela_ate){
							$QtdParcelasMax = $p;
						}
					}
					else {
						$QtdParcelasMax = $p-1;
					}
				}
			}
		
			if(isset($QtdParcelasMax)){
				$data = array(
					'price' 			=> $this->getPrice(), 
					'price_parcelado'	=> number_format((double)($this->getPrice()/$QtdParcelasMax), "2", ",", "."),
					'parcelamento_max' 	=> $QtdParcelasMax,
				);

				return $data;
			}
		}

		return array();
	}
}