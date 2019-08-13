<?php
$installer = $this;
$installer->startSetup();

$installer->run("ALTER TABLE  `".$this->getTable('sales_flat_order')."` ADD is_clicksend_send tinyint(1)");

$installer->endSetup(); 
?>