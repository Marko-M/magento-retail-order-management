<?php
/**
 * Responsible for handling the AddressValidationResponse message from EB2C.
 */
class TrueAction_Eb2c_Address_Model_Validation_Response
	extends Mage_Core_Model_Abstract
{

	/**
	 * Mapping of XPath expressions used to fetch various parts of the message
	 * @var array
	 */
	protected static $_paths = array(
		'request_address'  => 'eb2c:AddressValidationResponse/eb2c:RequestAddress',
		'result_code'      => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ResultCode',
		'result_errors'    => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ErrorLocations/eb2c:ErrorLocation',
		'suggestion_count' => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ResultSuggestionCount',
		'suggestions'      => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:SuggestedAddresses/eb2c:SuggestedAddress',
		'provider_error'   => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ProviderErrorText'
	);

	/**
	 * @var TrueAction_Eb2c_Address_Helper_Data
	 */
	protected $_helper;

	/**
	 * @var TrueAction_Dom_Document
	 */
	protected $_doc;

	protected function _construct()
	{
		$this->_helper = Mage::helper('eb2caddress');
		$this->_doc = new TrueAction_Dom_Document();
	}

	/**
	 * Load the response message into the dom document.
	 * @param string $message - the XML response message
	 * @return TrueAction_Eb2c_Address_Model_Validation_Response $this
	 */
	public function setMessage($message)
	{
		$this->_doc->loadXML($message);
		// new message means any stored data on this instance is probably invalid, so nuke it
		$this->unsetData();
		return $this;
	}

	/**
	 * Pass through to the TrueAction_Eb2c_Address_Helper_Data::getTextValueByXPath method.
	 * @param string $path - XPath expressions
	 * @param DOMNode $context - when unspecified, will use the stored DOMDocument for the message - $this->_doc
	 * @return string|array
	 */
	protected function _lookupPath($pathKey, DOMNode $context = null)
	{
		return $this->_helper
			->getTextValueByXPath(self::$_paths[$pathKey], $context ?: $this->_doc);
	}

	/**
	 * When there is a valid address in the response, return it. Otherwise
	 * this should return null, as a "valid address" does not exist in the response.
	 * @return Mage_Customer_Model_Address
	 */
	public function getValidAddress()
	{
		if (!$this->hasData('valid_address')) {
			$validAddress = null;
			if ($this->isAddressValid()) {
				if ((int) $this->_lookupPath('suggestion_count') === 1) {
					$suggestions = $this->getAddressSuggestions();
					$validAddress = $suggestions[0];
				} else {
					$validAddress = $this->getOriginalAddress();
				}
			}
			$this->setData('valid_address', $validAddress);
		}
		return $this->getData('valid_address');
	}

	/**
	 * Gets the original address submitted to the service.
	 * @return Mage_Customer_Model_Address
	 */
	public function getOriginalAddress()
	{
		if (!$this->hasData('original_address')) {
			$xpath = new DOMXPath($this->_doc);
			$xpath->registerNamespace(
				'eb2c',
				$this->_doc->lookupNamespaceUri($this->_doc->namespaceURI)
			);

			$physicalAddressElement = $xpath->query(
				self::$_paths['request_address'],
				$this->_doc)->item(0);
			$this->setData(
				'original_address',
				$this->_helper->physicalAddressXmlToAddress($physicalAddressElement)
					->setHasBeenValidated(true)
			);
		}
		return $this->getData('original_address');
	}

	/**
	 * Does the response message include suggestions?
	 * @return boolean
	 */
	public function hasAddressSuggestions()
	{
		if (!$this->hasData('has_address_suggestions')) {
			$suggestionCount = (int) $this->_lookupPath('suggestion_count');
			$this->setData('has_address_suggestions', ($suggestionCount > 1));
		}
		return $this->getData('has_address_suggestions');
	}

	/**
	 * Get the list of suggested addresses returned by the service.
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getAddressSuggestions()
	{
		if (!$this->hasData('address_suggestions')) {
			$xpath = new DOMXPath($this->_doc);
			$xpath->registerNamespace(
				'eb2c',
				$this->_doc->lookupNamespaceUri($this->_doc->namespaceURI)
			);

			$physicalAddressElements = $xpath->query(self::$_paths['suggestions'], $this->_doc);
			$suggestionAddresses = array();
			foreach ($physicalAddressElements as $physicalAddress) {
				$suggestionAddresses[] = $this->_helper
					->physicalAddressXmlToAddress($physicalAddress)
					->setHasBeenValidated(true);
			}
			$this->setData('address_suggestions', $suggestionAddresses);
		}
		return $this->getData('address_suggestions');
	}

	/**
	 * Indicates if the address should be considered valid.
	 * @return bool
	 */
	public function isAddressValid()
	{
		if (!$this->hasData('is_valid')) {
			$resultCode = $this->_lookupPath('result_code');
			$validity;
			switch ($resultCode) {
				case 'V':
					$validity = true;
					break;
				case 'C':
					if ($this->hasAddressSuggestions()) {
						$validity = false;
					} else {
						$validity = true;
					}
					break;
				case 'K':
					$validity = false;
					break;
				case 'N':
					$validity = true;
					break;
				case 'U':
					Mage::log('EB2C Address Validation: Unable to contact provider', Zend_Log::WARN);
					$validity = true;
					break;
				case 'T':
					Mage::log('EB2C Address Validation: Provider timed out', Zend_Log::WARN);
					$validity = true;
					break;
				case 'P':
					Mage::log('EB2C Address Validation: Provider returned a system error', Zend_Log::WARN);
					Mage::log($this->_lookupPath('provider_error'), Zend_Log::DEBUG);
					$validity = true;
					break;
				case 'M':
					Mage::log('EB2C Address Validation: The request message was malformed or contained invalid data', Zend_Log::WARN);
					$validity = true;
					break;
				default:
					Mage::log(sprintf('EB2C Address Validation: The response message did not contain a known result code. Result Code: %s', $resultCode), Zend_Log::WARN);
					$validity = true;
					break;
			}
			$this->setData('is_valid', $validity);
		}
		return $this->getData('is_valid');
	}

}