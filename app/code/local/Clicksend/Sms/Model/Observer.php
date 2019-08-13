<?php
class Clicksend_Sms_Model_Observer
{
	public function orderSave(Varien_Event_Observer $observer)
	{
		try {
			$order = $observer->getOrder();
			if ($order && $order->getId() > 0) {
				if (Mage::helper('clicksendsms')->getIsClicksendSent($order->getId()) != 1) {
					if (Mage::getStoreConfig('clicksendsms/messages/sendsmsonneworder') == 1) {
						$countryCode = 'IN';Mage::getStoreConfig('general/country/default');
						$body = Mage::helper('clicksendsms')->getNewOrderMessage($order);
						$to = Mage::getStoreConfig('clicksendsms/settings/adminphone');
						Mage::helper('clicksendsms')->sendSMS($to, $body, 'New_Order', $order->getId(), $countryCode);
						Mage::helper('clicksendsms')->setIsClicksendSent($order->getId());
					}
				} elseif ($order->getOrigData('status') != $order->getStatus()) {
					if ($order->hasShipments() && Mage::getStoreConfig('clicksendsms/messages/sendsmsonship') == 1) {
						$address = $order->getShippingAddress();
						if ($address) {
							$countryCode = $address->getCountryId();
							$to = $address->getTelephone();
						} else {
							$address = $order->getBillingAddress();
							$countryCode = $address->getCountryId();
							$to = $address->getTelephone();
						}
						$body = Mage::helper('clicksendsms')->getShippedStatusMessage($order);
						Mage::helper('clicksendsms')->sendSMS($to, $body, 'Shipped_Status', $order->getId(), $countryCode);
					}
				}
			} 
		} catch(Exception $e) {
			Mage::log($order->getId().': Order Save, Exception:'.$e->getMessage(), null, 'clicksendsms.log', true);
		}
	}
}