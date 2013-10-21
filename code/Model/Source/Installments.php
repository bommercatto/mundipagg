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

class Uecommerce_Mundipagg_Model_Source_Installments {
    public function toOptionArray() {
        return array(
            array('value' => '2', 'label' => '2'),
            array('value' => '3', 'label' => '3'),
            array('value' => '4', 'label' => '4'),
            array('value' => '5', 'label' => '5'),
            array('value' => '6', 'label' => '6'),
            array('value' => '7', 'label' => '7'),
            array('value' => '8', 'label' => '8'),
            array('value' => '9', 'label' => '9'),
            array('value' => '10', 'label' => '10'),
            array('value' => '11', 'label' => '11'),
            array('value' => '12', 'label' => '12'),
        );
    }
}