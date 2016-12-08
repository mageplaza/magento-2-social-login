<?php
namespace Mageplaza\SocialLogin\Block\Popup\Authentication;

use Mageplaza\SocialLogin\Block\SocialLogin;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;

class Social extends SocialLogin
{
	const SOCIALS_LIST = [
		'facebook'   => 'Facebook',
		'google'     => 'Google',
		'twitter'    => 'Twitter',
		'linkedin'   => 'LinkedIn',
		'instagram'  => 'Instagram',
		'yahoo'      => 'Yahoo',
		'github'     => 'Github',
		'vkontakte'  => 'Vkontakte',
		'foursquare' => 'Foursquare',
		'live'       => 'Live'
	];

	protected $socialHelper;

	public function __construct(
		Context $context,
		HelperData $helperData,
		SocialHelper $socialHelper,
		ObjectManagerInterface $objectManager,
		CustomerSession $customerSession,
		array $data = []
	)
	{
		$this->socialHelper = $socialHelper;

		parent::__construct($context, $helperData, $objectManager, $customerSession, $data);
	}

	public function getAvailableSocials()
	{
		$availabelSocials = [];

		foreach (self::SOCIALS_LIST as $socialKey => $socialLabel) {
			$helper = $this->socialHelper->correctXmlPath($socialKey);

			if ($helper->isEnabled() && $helper->getAppId() && $helper->getAppSecret()) {
				$availabelSocials[$socialKey] = new \Magento\Framework\DataObject([
					'label'     => $socialLabel,
					'login_url' => $this->getLoginUrl($socialKey)
				]);
			}
		}

		return $availabelSocials;
	}

	public function getLoginUrl($socialKey)
	{
		return $this->getUrl('sociallogin/' . $socialKey . '/login');
	}
}
