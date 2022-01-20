<?php
class ModelExtensionPaymentMoneyCollect extends Model {
	public function install() {
	    $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "moneycollect_customer` (
			  `customer_id` INT(11) NOT NULL,
			  `moneycollect_customer_id` varchar(255) NOT NULL,
			  PRIMARY KEY (`moneycollect_customer_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "moneycollect_customer`");
	}
}
