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

class EbayEnterprise_Eb2cProduct_Model_Observers
{
	/**
	 * This observer locks attributes we've configured as read-only
	 * @return void
	 */
	public function lockReadOnlyAttributes(Varien_Event_Observer $observer)
	{
		$readOnlyAttributesString = Mage::helper('eb2cproduct')->getConfigModel()->readOnlyAttributes;
		// We use preg_split's PREG_SPLIT_NO_EMPTY so multiple ',' won't populate an array slot
		//  with an empty string. A single string without separators ends up at index 0.
		$readOnlyAttributes = preg_split('/,/', $readOnlyAttributesString, -1, PREG_SPLIT_NO_EMPTY);
		if ($readOnlyAttributes) {
			$product = $observer->getEvent()->getProduct();
			foreach ($readOnlyAttributes as $readOnlyAttribute) {
				$product->lockAttribute($readOnlyAttribute);
			}
		}
	}
	/**
	 * Listen to the 'ebayenterprise_feed_dom_loaded' event
	 * @see EbayEnterprise_Eb2cCore_Model_Feed_Abstract::processFile
	 * process a dom document
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function processDom(Varien_Event_Observer $observer)
	{
		Varien_Profiler::start(__METHOD__);
		$event = $observer->getEvent();
		$fileDetail = $event->getFileDetail();
		$importConfig = Mage::getModel('eb2cproduct/feed_import_config');
		$importData = $importConfig->getImportConfigData();
		$feedConfig = $fileDetail['core_feed']->getFeedConfig();

		// only process the import if the event type is in the allowabled event type configuration for this feed
		if (in_array($feedConfig['event_type'], explode(',', $importData['allowable_event_type']))) {
			Mage::log(sprintf('[%s] processing %s', __METHOD__, $fileDetail['local_file']), Zend_Log::DEBUG);
			$fileDetail['doc'] = $event->getDoc();
			Mage::getModel('eb2cproduct/feed_file', $fileDetail)->process(
				$importConfig, Mage::getModel('eb2cproduct/feed_import_items')
			);
		}
		Varien_Profiler::stop(__METHOD__);
		return $this;
	}
}