<?php

/**
 * Stubs for use by the helper class for looking up config paths from keys.
 */
class Config_Stub implements TrueAction_Eb2c_Core_Model_Config_Interface
{

	public function hasKey($key)
	{
		return $key === "catalog_id" || $key === "api_key" || $key === "test_mode";
	}

	public function getPathForKey($key)
	{
		$paths = array("catalog_id" => "eb2c/core/catalog_id", "api_key" => "eb2c/core/api_key", "test_mode" => "eb2c/core/test_mode");
		return $paths[$key];
	}

}

class Alt_Config_Stub implements TrueAction_Eb2c_Core_Model_Config_Interface
{

	public function hasKey($key)
	{
		return $key === "catalog_id" || $key === "another_setting";
	}

	public function getPathForKey($key)
	{
		$paths = array("catalog_id" => "eb2c/another/catalog_id", "another_setting" => "eb2c/another/module/setting");
		return $paths[$key];
	}

}

/**
 * Test the helper/config class. Should ensure that:
 * - Looking up a config value through the helper returns
 *   the same results as looking it up through the
 *   Mage::getStoreConfig or Mage::getStoreConfigFlag methods.
 * - The appropriate store view is used when looking up config values
 * - Multiple config classes can be used to look up paths
 * - When using multiple config models, the last one in takes precedence
 */
class TrueAction_Eb2c_Core_Test_Helper_ConfigTest extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * @test
	 * @loadFixture configData
	 */
	public function testGetConfig()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->addConfigModel(new Config_Stub());

		// ensure a value is returned
		$this->assertNotNull($config->getConfig('catalog_id'));

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->getConfig('catalog_id'), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// when explicitly passing a storeId, should return value for that store
		$this->assertSame($config->getConfig('api_key', 2), Mage::getStoreConfig('eb2c/core/api_key', 2));

		// when store id is set on config object, will use that value for the store id
		$config->setStore(2);
		$this->assertSame($config->getConfig('catalog_id'), Mage::getStoreConfig('eb2c/core/catalog_id', 2));

		// can still use an explicit store id which should override the one set on the store
		$this->assertSame($config->getConfig('api_key', 3), Mage::getStoreConfig('eb2c/core/api_key', 3));

		// can even explicitly set store id to null
		$this->assertSame($config->getConfig('catalog_id', null), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// indicate the config is a "flag" to explicitly return a boolean value
		$this->assertSame($config->getConfigFlag('test_mode', 1), Mage::getStoreConfigFlag('eb2c/core/test_mode', 1));
	}

	/**
	 * If getConfig is called and the key is not found, an exception should be raised.
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testConfigNotFoundExceptions()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->getConfig('nonexistent_config');
	}

	/**
	 * @test
	 * @loadFixture configData
	 */
	public function testMagicPropConfig()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->addConfigModel(new Config_Stub())
			->addConfigModel(new Alt_Config_Stub());

		// should get some config value for both of these
		// this will come from the first Config_Stub
		$this->assertNotNull($config->apiKey);
		// this will come from the second Alt_Config_Stub
		$this->assertNotNull($config->anotherSetting);

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->apiKey, Mage::getStoreConfig('eb2c/core/api_key'));

		// should be able to get config added by either settings model
		$this->assertSame($config->anotherSetting, Mage::getStoreConfig('eb2c/another/module/setting'));

		// keys can collide...last added are used
		// this path is in the first config model added and will not be used
		$this->assertNotSame($config->catalogId, Mage::getStoreConfig('eb2c/core/catalog_id'));
		// this is in the second config model and will be used
		$this->assertSame($config->catalogId, Mage::getStoreConfig('eb2c/another/catalog_id'));
	}

	/**
	 * @test
	 * @expectedException Exception
	 */
	public function testUnknownProp()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->nonexistentConfig;
	}
}