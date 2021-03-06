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


class EbayEnterprise_Eb2cCustomerService_Test_Model_Token_ResponseTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test checking the validity of the token, for now, this basically just
     * means a successful response/any response message at all.
     */
    public function testIsTokenValid()
    {
        $message = '<MockTokenResponse/>';
        $response = Mage::getModel('eb2ccsr/token_response', array('message' => $message));
        $this->assertTrue($response->isTokenValid());
        $response->setMessage('');
        $this->assertFalse($response->isTokenValid());
    }
    public function testGetCSRData()
    {
        $message = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<TokenValidateResponse xmlns="http://schema.gspt.net/token/1.0">
	<Token>49c89514-f9ff-4be1-914c-22800d2c5f0a</Token>
	<Data>
		<Field key="rep_name">Neelima Chivukula</Field>
		<Field key="rep_permissions">111</Field>
		<Field key="entry_point">homepage</Field>
		<Field key="store_id">TMSGB</Field>
		<Field key="rep_id">CB07C52BAC15694800521A239D26FD06</Field>
		<Field key="interaction_id">CB765C7FAC15694800647A230DC3C7D8</Field>
	</Data>
</TokenValidateResponse>';
        $response = Mage::getModel('eb2ccsr/token_response', array('message' => $message));
        $this->assertSame(
            array(
                'rep_name' => 'Neelima Chivukula',
                'rep_permissions' => '111',
                'entry_point' => 'homepage',
                'store_id' => 'TMSGB',
                'rep_id' => 'CB07C52BAC15694800521A239D26FD06',
                'interaction_id' => 'CB765C7FAC15694800647A230DC3C7D8',
            ),
            $response->getCSRData()
        );
    }
    public function testGetCSRDataNoMessage()
    {
        $response = Mage::getModel('eb2ccsr/token_response');
        $this->assertSame(array(), $response->getCSRData());
    }
}
