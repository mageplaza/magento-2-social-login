<?php
namespace Mageplaza\SocialLogin\Controller\Social;

use Mageplaza\SocialLogin\Model\Social;
use Magento\Framework\App\Action\Context;

class Callback extends \Magento\Framework\App\Action\Action
{
	protected $social;

	public function __construct(
		Context $context,
		Social $social

	)
	{
		parent::__construct($context);
		$this->social = $social;
	}

	public function execute()
	{
		$endPoint = $this->social->getEndpoint();
		$endPoint->process();
	}

}