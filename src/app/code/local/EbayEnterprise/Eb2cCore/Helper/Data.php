<?php
class EbayEnterprise_Eb2cCore_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Service URI has the following format:
	 * https://{host}/v{M}.{m}/stores/{storeid}/{service}/{operation}{/parameters}.{format}
	 * - host - EE Excnahge Platform domain
	 * - M - major version of the API
	 * - m - minor version of the API
	 * - storeid - GSI assigned store identifier
	 * - service - API call service/subject area
	 * - operation - specific API call of the specified service
	 * - parameters - optionally any parameters needed by the call
	 * - format - extension of the requested response format. Currently only xml is supported
	 */
	const URI_FORMAT = 'https://%s/v%s.%s/stores/%s/%s/%s%s.%s';
	const PERMISSION = 0750;
	/**
	 * Get the API URI for the given service/request.
	 * @param string $service
	 * @param string $operation
	 * @param array $params
	 * @param string $format
	 */
	public function getApiUri($service, $operation, $params=array(), $format='xml')
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		return sprintf(
			self::URI_FORMAT,
			$config->apiHostname,
			$config->apiMajorVersion,
			$config->apiMinorVersion,
			$config->storeId,
			$service,
			$operation,
			(!empty($params)) ? '/' . implode('/', $params) : '',
			$format
		);
	}

	/**
	 * Create and return a new instance of EbayEnterprise_Dom_Document.
	 * @return EbayEnterprise_Dom_Document
	 */
	public function getNewDomDocument()
	{
		return new EbayEnterprise_Dom_Document('1.0', 'UTF-8');
	}

	/**
	 * given DOMDocument get the DOMElement from it.
	 * @param DOMDocument $doc
	 * @return DOMELement
	 * @codeCoverageIgnore
	 */
	public function getDomElement(DOMDocument $doc)
	{
		return $doc->documentElement;
	}

	/**
	 * Create and return a new instance of XSLTProcessor.
	 * @return XSLTProcessor
	 * @codeCoverageIgnore
	 */
	public function getNewXsltProcessor()
	{
		return new XSLTProcessor();
	}

	/**
	 * Create and return a new instance of DOMXPath.
	 * @param DOMDocument $doc
	 * @return DOMXPath
	 * @codeCoverageIgnore
	 */
	public function getNewDomXPath(DOMDocument $doc)
	{
		return new DOMXPath($doc);
	}

	/**
	 * Create and return a new instance of SplObjectStorage.
	 * @return SplObjectStorage
	 * @codeCoverageIgnore
	 */
	public function getNewSplObjectStorage()
	{
		return new SplObjectStorage();
	}

	/**
	 * validating ftp settings by simply checking if there's actual setting data.
	 *
	 * @return bool, true valid ftp settings otherwise false
	 */
	public function isValidFtpSettings()
	{
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		return trim($cfg->sftpUsername) && trim($cfg->sftpLocation) && (
			($cfg->sftpAuthType === 'password' && trim($cfg->sftpPassword)) ||
			($cfg->sftpAuthType === 'pub_key' && trim($cfg->sftpPrivateKey))
		);
	}

	/**
	 * convert feed lang data to match magento expected format (en-US => en_US)
	 *
	 * @param string $langCode, the language code
	 *
	 * @return string, the magento expected format
	 */
	public static function xmlToMageLangFrmt($langCode)
	{
		return str_replace('-', '_', $langCode);
	}

	/**
	 * Convert Magento Locale Code into a format matching inbound feed, i.e. en_US => en-US
	 *
	 * @param string $langCode, the language code
	 * @return string, the magento expected format
	 */
	public static function mageToXmlLangFrmt($langCode)
	{
		return str_replace('_', '-', $langCode);
	}

	/**
	 * Ensure the sku/client id/style id matches the same format expected for skus
	 * {catalogId}-{item_id}
	 * @param  string $itemId    Product item/style/client/whatevs id
	 * @param  string $catalogId Product catalog id
	 * @return string            Normalized style id
	 */
	public function normalizeSku($itemId, $catalogId)
	{
		if (!empty($itemId)) {
			$pos = strpos($itemId, $catalogId . '-');
			if ($pos === false || $pos !== 0) {
				return sprintf('%s-%s', $catalogId, $itemId);
			}
		}
		return $itemId;
	}

	/**
	 * Get value of node specified for query. In accordance with existing local custom here,
	 * named 'extract', and calls extractNodeValue to ensure consistency.
	 * @param DOMXpath xpath object
	 * @param xpath query string
	 * @return string value found at node found by query
	 */
	public function extractQueryNodeValue(DOMXpath $xpath, $query)
	{
		return $this->extractNodeVal($xpath->query($query));
	}

	/**
	 * extract node value
	 *
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 */
	public function extractNodeVal(DOMNodeList $nodeList)
	{
		return ($nodeList->length)? $nodeList->item(0)->nodeValue : null;
	}

	/**
	 * extract node attribute value
	 *
	 * @return string, the extracted content
	 * @param DOMNodeList $nodeList
	 * @param string $attributeName
	 */
	public function extractNodeAttributeVal(DOMNodeList $nodeList, $attributeName)
	{
		return ($nodeList->length)? $nodeList->item(0)->getAttribute($attributeName) : null;
	}

	/**
	 * Return the eb2c ship method configured to correspond to a known Magento ship method.
	 * @param string $mageShipMethod
	 * @return string EB2C ship method
	 */
	public function lookupShipMethod($mageShipMethod)
	{
		// Deliberately bypass configurator so we can dynamically lookup.
		return Mage::getStoreConfig("eb2ccore/shipmap/$mageShipMethod");
	}

	/**
	 * loading a file into a new SplFileInfo instantiated object
	 * @param string $fileName
	 * @return SplFileInfo
	 * @codeCoverageIgnore
	 */
	public function loadFile($fileName)
	{
		return new SplFileInfo($fileName);
	}

	/**
	 * abstracting calling file exists
	 * @param string $fileName
	 * @return bool true|false
	 * @codeCoverageIgnore
	 */
	public function isFileExist($fileName)
	{
		return $this->loadFile($fileName)->isReadable();
	}

	/**
	 * abstracting calling is dir
	 * @param string $dir
	 * @return bool true|false
	 * @codeCoverageIgnore
	 */
	public function isDir($dir)
	{
		return $this->loadFile($dir)->isDir();
	}

	/**
	 * abstracting creating dir
	 * @param string $dir
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function createDir($dir)
	{
		$oldMask = umask(0);
		@mkdir($dir, self::PERMISSION, true);
		umask($oldMask);
	}

	/**
	 * abstracting moving a file
	 * @param string $source
	 * @param string $destination
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function moveFile($source, $destination)
	{
		@rename($source, $destination);

		if (!$this->isFileExist($destination)) {
			throw new EbayEnterprise_Eb2cCore_Exception_Feed_File("Can not move $source to $destination");
		}
	}
	/**
	 * abstracting removing a file
	 * @param string $file
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function removeFile($file)
	{
		@unlink($file);

		if ($this->isFileExist($file)) {
			throw new EbayEnterprise_Eb2cCore_Exception_Feed_File("Can not remove $file");
		}
	}

	/**
	 * abstracting getting store configuration value
	 * @see Mage::getStoreConfig
	 * @param string $path
	 * @param mixed $store
	 * @return mixed
	 * @codeCoverageIgnore
	 */
	public function getStoreConfig($path, $store=null)
	{
		return Mage::getStoreConfig($path, $store);
	}

	/**
	 * abstracting getting store configuration flag
	 * @see Mage::getStoreConfigFlag
	 * @param string $path
	 * @param mixed $store
	 * @return bool
	 * @codeCoverageIgnore
	 */
	public function getStoreConfigFlag($path, $store=null)
	{
		return Mage::getStoreConfigFlag($path, $store);
	}
	/**
	 * abstracting getting the magento base directory with a given scope or default
	 * scope and a given relative path to build an absolute full path
	 * @see Mage::getBaseDir
	 * @param string $relative
	 * @param string $scope
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getAbsolutePath($relative, $scope='base')
	{
		return Mage::getBaseDir($scope) . DS . $relative;
	}
	/**
	 * @see invokeCallback of EbayEnterprise_Eb2cCore_Helper_Feed
	 * @param  array  $callback
	 * @return mixed
	 */
	public function invokeCallback(array $callback=array())
	{
		return Mage::helper('eb2ccore/feed')->invokeCallback($callback);
	}

	/**
	 * getting how long ago a file has been modified or created in minutes
	 * @param string $sourceFile
	 * @return int time ago in minutes
	 */
	public function getFileTimeElapse($sourceFile)
	{
		$date = Mage::getModel('core/date');
		$timeZone = $this->getNewDateTimeZone();
		$startDate = $this->getNewDateTime(
			$date->gmtDate('Y-m-d H:i:s', $this->loadFile($sourceFile)->getCTime()),
			$timeZone
		);
		$interVal = $startDate->diff($this->getNewDateTime(
			$date->gmtDate('Y-m-d H:i:s', $this->getTime()),
			$timeZone
		));
		return (
			($interVal->y * 365 * 24 * 60) +
			($interVal->m * 30 * 24 * 60) +
			($interVal->d * 24 * 60) +
			($interVal->h * 60) +
			$interVal->i
		);
	}
	/**
	 * abstracting instantiating a new DataTime object
	 * @param $time
	 * @param DateTimeZone $timezone
	 * @return DateTime
	 * @codeCoverageIgnore
	 */
	public function getNewDateTime($time='now', DateTimeZone $timezone=null)
	{
		return new DateTime($time, $timezone);
	}
	/**
	 * abstracting instantiating a new DateTimeZone in the default
	 * magento config time zone
	 * @return DateTimeZone
	 * @codeCoverageIgnore
	 */
	public function getNewDateTimeZone()
	{
		return new DateTimeZone($this->getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE));
	}
	/**
	 * abstracting getting the current time
	 * @return int
	 * @codeCoverageIgnore
	 */
	public function getTime()
	{
		return time();
	}
	/**
	 * @see getConfigData of EbayEnterprise_Eb2cCore_Helper_Feed
	 * @param  string  $configPath
	 * @return mixed
	 */
	public function getConfigData($configPath)
	{
		return Mage::helper('eb2ccore/feed')->getConfigData($configPath);
	}

	/**
	 * given a product object and a country code retrieve the hts_code value for this product
	 * matching a given country code
	 * @param Mage_Catalog_Model_Product $product
	 * @param string $countryCode the two letter code for a country (US, CA, DE, etc...)
	 * @return string | null the htscode matching the country code for that product otherwise null
	 */
	public function getProductHtsCodeByCountry(Mage_Catalog_Model_Product $product, $countryCode)
	{
		$htsCodes = unserialize($product->getHtsCodes());
		if ($htsCodes) {
			foreach ($htsCodes as $htsCode) {
				if ($countryCode === $htsCode['destination_country']) {
					return $htsCode['hts_code'];
				}
			}
		}

		return null;
	}

	/**
	 * abstracting getting an instance of the default store
	 * @return Mage_Core_Model_Store
	 * @codeCoverageIgnore
	 */
	public function getDefaultStore()
	{
		return $this->getDefaultWebsite()->getDefaultStore();
	}

	/**
	 * abstracting getting an instance of the default website
	 * @return Mage_Core_Model_Website
	 * @codeCoverageIgnore
	 */
	public function getDefaultWebsite()
	{
		return Mage::app()->getWebsite(true);
	}

	/**
	 * abstracting getting store configuration flag
	 * @see Mage::getBaseUrl
	 * @codeCoverageIgnore
	 */
	public function getBaseUrl($type = Mage_Core_Model_Store::URL_TYPE_LINK, $secure = null)
	{
		return Mage::getBaseUrl($type, $secure);
	}

	/**
	 * abstracting triggering errors
	 * @param string $message
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function triggerError($message)
	{
		trigger_error($message, E_USER_ERROR);
	}
}