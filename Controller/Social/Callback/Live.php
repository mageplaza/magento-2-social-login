<?php
namespace Mageplaza\SocialLogin\Controller\Social\Callback;

use Mageplaza\SocialLogin\Model\Social;
use Magento\Framework\App\Action\Context;

class Live extends \Magento\Framework\App\Action\Action
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
		$_REQUEST['hauth_done'] = 'Live';

		$endPoint = $this->social->getEndpoint();
		$endPoint->process();
	}

}