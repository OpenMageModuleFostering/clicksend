<?php
class Clicksend_Sms_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getVariables()
    {
        $variables = array();
        $variables[] = '{ORDER-NUMBER}';
        $variables[] = '{ORDER-TOTAL}';
        $variables[] = '{ORDER-STATUS}';
        $variables[] = '{CARRIER-NAME}';
        $variables[] = '{PAYMENT-NAME}';
        $variables[] = '{CUSTOMER-NAME}';
        $variables[] = '{CUSTOMER-EMAIL}';
        return $variables;
    }
	public function getIsClicksendSent($order_id)
	{
		$resource = Mage::getSingleton('core/resource');
	    $readConnection = $resource->getConnection('core_read');
	    $table = $resource->getTableName('sales_flat_order');

		$query = "select is_clicksend_send from {$table} where entity_id = ".(int)($order_id);
		return (int)($readConnection->fetchOne($query));
	}
	public function setIsClicksendSent($order_id)
	{
		$resource = Mage::getSingleton('core/resource');
	    $writeConnection = $resource->getConnection('core_write');
	    $table = $resource->getTableName('sales_flat_order');

		$query = "update {$table} set is_clicksend_send=1 where entity_id = ".(int)($order_id);
		$writeConnection->query($query);
	}
	public function getIncrementId($order)
	{
		$incrementId = $order->getOriginalIncrementId();
		if($incrementId == null || empty($incrementId) || !$incrementId)
		{
			$incrementId = $order->getIncrementId();
	  	}
		return $incrementId;
	}
    public function getNewOrderMessage($order)
    {
        $variables = $this->getVariables();
        $values =  array();
        $values[] = $this->getIncrementId($order);
        $values[] = Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->toCurrency($order->getGrandTotal());
        $values[] = $order->getStatus();
        $values[] = $order->getShippingDescription();
        $values[] = $order->getPayment()->getMethodInstance()->getTitle();
        $values[] = $order->getCustomerFirstname().' '.$order->getCustomerLastname();
        $values[] = $order->getCustomerEmail();
        $message = Mage::getStoreConfig('clicksendsms/messages/sendsmsonnewordermessage');
        return  str_replace($variables, $values, $message);
    }
    public function getShippedStatusMessage($order)
    {
        $variables = $this->getVariables();
        $values =  array();
        $values[] = $this->getIncrementId($order);
        $values[] = Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->toCurrency($order->getGrandTotal());
        $values[] = $order->getStatus();
        $values[] = $order->getShippingDescription();
        $values[] = $order->getPayment()->getMethodInstance()->getTitle();
        $values[] = $order->getCustomerFirstname().' '.$order->getCustomerLastname();
        $values[] = $order->getCustomerEmail();
        $message = Mage::getStoreConfig('clicksendsms/messages/sendsmsonshipmessage');
        return  str_replace($variables, $values, $message);
    }
    public function sendSMS($to, $body, $action, $id_order, $countryCode)
    {
        try {
            $source = 'magento';
            $from = Mage::getStoreConfig('clicksendsms/settings/sender');
            $username = Mage::getStoreConfig('clicksendsms/settings/username');
            $password = Mage::getStoreConfig('clicksendsms/settings/password');
            $data = array();
            $message = array();
            $message['source'] = $source;
            $message['from'] = $from;
            $message['body'] = $body;
            $message['to'] = $to;
            $message['country'] = $countryCode;
            $data['messages'][] = $message;
            $data = json_encode($data);
            $this->log("{$id_order}-{$action}-Request");
            $this->log($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://rest.clicksend.com/v3/sms/send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Content-Length: ".strlen($data),
            "Authorization: Basic ".base64_encode($username.':'.$password)
            ));
            $response = curl_exec($ch);
            if (!$response) {
                throw new Exception(curl_error($ch));
            }
            curl_close($ch);
            $this->log("{$id_order}-{$action}-Response");
            $this->log($response);
        } catch (Exception $e) {
            $this->log("{$id_order}-{$action}-Response: ".$e->getMessage());
        }
    }
    public function log($content)
    {
        if (Mage::getStoreConfig('clicksendsms/settings/debug')==1) {
            Mage::log($content, null, 'clicksendsms.log', true);
        }
    }
}