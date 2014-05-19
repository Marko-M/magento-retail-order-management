<?php
/**
 * Largely a clone of the Adminhtml Validate VAT block - slight changes to use
 * the proper templates and URLs.
 * @see Mage_Adminhtml_Block_Customer_System_Config_Validatevat
 * @codeCoverageIgnore Copy of Magento - no significant changes to add useful tests for
 */
class EbayEnterprise_Eb2cCore_Block_System_Config_Testsftpconnection
	extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/**
	 * Make sure the template gets set appropriately
	 * @return self
	 */
	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		if (!$this->getTemplate()) {
			$this->setTemplate('eb2ccore/system/config/testsftpconnection.phtml');
		}
		return $this;
	}

	/**
	 * Unset some non-related element parameters
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
		return parent::render($element);
	}

	/**
	 * Get the button and scripts contents
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$originalData = $element->getOriginalData();
		$this->addData(array(
			'button_label' => Mage::helper('eb2ccore')->__($originalData['button_label']),
			'html_id' => $element->getHtmlId(),
			'ajax_url' => $this->getUrl('*/exchange_system_config_validate/validatesftp', array('_current' => true))
		));

		return $this->_toHtml();
	}
}
