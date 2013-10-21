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

$installer = $this;

$installer->startSetup();

$reader = Mage::getSingleton('core/resource')->getConnection('core_read');

$prefix = Mage::getConfig()->getTablePrefix();

$installer->run("

CREATE TABLE IF NOT EXISTS `".$prefix."mundipagg_transactions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `boleto_url` varchar(100) DEFAULT NULL,
  `authentication_url` varchar(100) DEFAULT NULL,
  `auth_code` varchar(100) DEFAULT NULL,
  `reference_num` varchar(100) DEFAULT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `transaction_timestamp` varchar(100) DEFAULT NULL,
  `response_code` varchar(100) DEFAULT NULL,
  `response_message` varchar(100) DEFAULT NULL,
  `avs_response_code` varchar(100) DEFAULT NULL,
  `processor_code` varchar(100) DEFAULT NULL,
  `processor_message` varchar(100) DEFAULT NULL,
  `processor_reference_number` varchar(100) DEFAULT NULL,
  `processor_transaction_id` varchar(100) DEFAULT NULL,
  `error_message` varchar(100) DEFAULT NULL,
  `save_on_file_token` varchar(100) DEFAULT NULL,
  `save_on_file_error` varchar(100) DEFAULT NULL,
  `error` TEXT,
  PRIMARY KEY (`id`),
  KEY `reference_num` (`reference_num`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `".$prefix."mundipagg_requests` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) DEFAULT NULL,
  `entity_id_mundipagg` int(10) DEFAULT NULL,
  `error` varchar(255) DEFAULT NULL,
  `error_code` varchar(255) DEFAULT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `command` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entity_id` (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup(); 
?>