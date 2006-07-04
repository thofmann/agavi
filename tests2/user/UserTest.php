<?php
class SampleUser extends AgaviUser
{
}

class UserTest extends AgaviTestCase
{
	private $_u = null;

	public function setUp()
	{
		$this->_u = new SampleUser();
		$context = AgaviContext::getInstance();
		$this->_u->initialize($context);
	}

	public function testInitialize()
	{
		$ctx = AgaviContext::getInstance();
		$u = $this->_u;

		$this->assertReference($ctx, $u->getContext());
		$this->assertEquals('org.agavi.user.User', $u->getStorageNamespace());

		$u->initialize($ctx, array('default_namespace' => 'default.test.ns', 'storage_namespace' => 'storage.test.ns'));
		$this->assertEquals('default.test.ns', $u->getDefaultNamespace());
		$this->assertEquals('storage.test.ns', $u->getStorageNamespace());

	}
}
?>