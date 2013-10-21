<?php
/**
 * 
 * @author Jonathan Ribas
 * 
 */

$installer = $this;

$installer->startSetup();

$reader = Mage::getSingleton('core/resource')->getConnection('core_read');

$prefix = Mage::getConfig()->getTablePrefix();

$installer->run("

DROP TABLE IF EXISTS `".$prefix."mundipagg_transactions`;

DROP TABLE IF EXISTS `".$prefix."mundipagg_requests`;

CREATE TABLE IF NOT EXISTS `".$prefix."mundipagg_card_on_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) NOT NULL,
  `address_id` int(10) NOT NULL,
  `cc_type` varchar(20) DEFAULT '',
  `credit_card_mask` varchar(20) NOT NULL,
  `expires_at` date DEFAULT NULL,
  `token` varchar(50) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entity_id` (`entity_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup(); 
?>