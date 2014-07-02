<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * A Feed Abstraction for implementing feed processors.
 */
abstract class EbayEnterprise_Eb2cCore_Model_Feed_Abstract extends Varien_Object
{
	protected $_coreFeed; // Handles file moving, listing, etc.
	/**
	 * Validate that the feed model has necessary configuration for the core
	 * feed model. Instantiate and store a core feed model using config data
	 * and optionally a fs_tool set in magic data.
	 * 
	 * @throws EbayEnterprise_Eb2cCore_Exception_Feed_Configuration
	 * @return self
	 */
	protected function _construct()
	{
		if(!$this->hasFeedConfig()) {
			throw new EbayEnterprise_Eb2cCore_Exception_Feed_Configuration(__CLASS__ . ' no configuration specifed.');
		}

		// Set up local folders for receiving, processing
		$coreFeedConstructorArgs = array(
			'feed_config' => $this->getFeedConfig()
		);

		// FileSystem tool can be supplied, esp. for testing
		if ($this->hasFsTool()) {
			$coreFeedConstructorArgs['fs_tool'] = $this->getFsTool();
		}

		// Ready to set up the core feed helper, which manages files and directories:
		$this->_coreFeed = Mage::getModel('eb2ccore/feed', $coreFeedConstructorArgs);
		return $this;
	}
	/**
	 * Get an array of file data for each file to process.
	 * @return array
	 */
	protected function _getFilesToProcess()
	{
		$coreFeed = $this->_coreFeed;
		return array_map(
			function ($file) use ($coreFeed) {
				return array('local_file' => $file, 'core_feed' => $coreFeed);
			},
			$this->_coreFeed->lsLocalDirectory()
		);
	}
	/**
	 * Fetches feeds from the remote, and then loops through all files found in the Inbound Dir.
	 *
	 * @return int Number of files we looked at.
	 */
	public function processFeeds()
	{
		$filesProcessed = 0;
		foreach ($this->_getFilesToProcess() as $feedFile) {
			try {
				$this->processFile($feedFile);
				$filesProcessed++;
			} catch (Mage_Core_Exception $e) {
				Mage::helper('ebayenterprise_magelog')->logWarn(
					'[%s] Failed to process file, %s. %s',
					array(__CLASS__, basename($feedFile['local_file']), $e->getMessage())
				);
			}
		}
		return $filesProcessed;
	}
	/**
	 * Load the file into a new DOM Document and validate the file. If successful,
	 * return the DOM document. Otherwise, return null.
	 * @param  array $fileDetail
	 * @return EbayEnterprise_Dom_Document|null
	 */
	protected function _loadDom($fileDetail)
	{
		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		if (!$dom->load($fileDetail['local_file'])) {
			Mage::log(
				sprintf('[%s] File %s: Failed to load as a DOM Document', __CLASS__, basename($fileDetail['local_file'])),
				Zend_Log::ERR
			);
			return null;
		}
		// Validate Eb2c Header Information
		if (!Mage::helper('eb2ccore/feed')->validateHeader($dom, $fileDetail['core_feed']->getEventType())) {
			Mage::log(
				sprintf('[%s] File %s: Invalid header', __CLASS__, basename($fileDetail['local_file'])),
				Zend_Log::ERR
			);
			return null;
		}
		return $dom;
	}

	/**
	 * Processes a single file using the data in the file detail. The given file
	 * detail can be expected to have, at the least:
	 * 'local_file': path to the file to be processed
	 * 'core_feed': reference to a EbayEnterprise_Eb2cCore_Model_Feed instance
	 *   configured for the type of feed file being processed
	 *
	 * @param array $fileDetail
	 * @return self
	 */
	public function processFile(array $fileDetail)
	{
		// after ack'ing the file, move it to the processing directory and reset
		// the 'local_file' path to the new location of the file in the
		// processing directory
		Mage::log(sprintf('[%s] Processing file %s', __CLASS__, $fileDetail['local_file']), Zend_Log::DEBUG);
		if ($dom = $this->_loadDom($fileDetail)) {
			$fileDetail['local_file'] = $fileDetail['core_feed']
				->acknowledgeReceipt($fileDetail['local_file'])
				->mvToProcessingDirectory($fileDetail['local_file']);
			Mage::dispatchEvent('ebayenterprise_feed_dom_loaded', array('file_detail' => $fileDetail, 'doc' => $dom));
		}
		$fileDetail['core_feed']->mvToImportArchive($fileDetail['local_file']);
		return $this;
	}
}
