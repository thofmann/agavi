<?php

class SampleModel extends AgaviModel {}

class TestAgaviModel extends AgaviTestCase
{
	public function testInitialize()
	{
		$context = AgaviContext::getInstance();
		$model = new SampleModel();
		$model->initialize($context);
	}

	public function testGetContext()
	{
		$context = AgaviContext::getInstance();
		$model = new SampleModel();
		$model->initialize($context);
		$mContext = $model->getContext();
		$this->assertReference($mContext, $context);
	}

}
?>