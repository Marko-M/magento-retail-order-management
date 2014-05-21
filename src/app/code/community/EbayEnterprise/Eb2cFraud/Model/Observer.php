<?php
class EbayEnterprise_Eb2cFraud_Model_Observer
{
	/**
	 * Handler called before order save.
	 * Updates quote with 41st Parameter anti-fraud JS.
	 * Observes sales_model_service_quote_submit_before
	 * @see Mage_Sales_Model_Service_Quote::submitOrder
	 * @param Varien_Event_Observer $observer Contains the order to be created and quote the order is created from
	 * @return self
	 */
	public function captureOrderContext($observer)
	{
		$timestamp = new DateTime();
		$http = Mage::helper('eb2cfraud/http');
		$hlpr = Mage::helper('eb2cfraud');
		$sess = Mage::getSingleton('customer/session');
		$rqst = $this->_getRequest();
		$observer->getEvent()->getOrder()->addData(array(
			'eb2c_fraud_char_set'        => $http->getHttpAcceptCharset(),
			'eb2c_fraud_content_types'   => $http->getHttpAccept(),
			'eb2c_fraud_encoding'        => $http->getHttpAcceptEncoding(),
			'eb2c_fraud_host_name'       => $http->getRemoteHost(),
			'eb2c_fraud_referrer'        => $sess->getOrderSource(),
			'eb2c_fraud_user_agent'      => $http->getHttpUserAgent(),
			'eb2c_fraud_language'        => $http->getHttpAcceptLanguage(),
			'eb2c_fraud_ip_address'      => $http->getRemoteAddr(),
			'eb2c_fraud_session_id'      => $sess->getEncryptedSessionId(),
			'eb2c_fraud_javascript_data' => $hlpr->getJavaScriptFraudData($rqst),
		));
		Mage::getSingleton('checkout/session')->addData(array(
			'eb2c_fraud_cookies'         => Mage::getSingleton('core/cookie')->get(),
			'eb2c_fraud_connection'      => $http->getHttpConnection(),
			'eb2c_fraud_session_info'    => $hlpr->getSessionInfo(),
			'eb2c_fraud_timestamp'       => $timestamp->format($hlpr::XML_DATETIME_FORMAT),
		));
	}

	/**
	 * get the request object in a way that can be stubbed in tests.
	 * @return Mage_Core_Controller_Request_Http
	 * @codeCoverageIgnore
	 */
	protected function _getRequest()
	{
		return Mage::app()->getRequest();
	}
}