<?php
namespace Mageplaza\SocialLogin\Controller\Instagram;

use Mageplaza\SocialLogin\Controller\AbstractSocial;

class Login extends AbstractSocial
{
	protected $socialType = 'Instagram';

	public function execute()
	{
		$user_profile = $this->getUserProfile();
		if (!$user_profile->identifier) {
			return $this->emailRedirect(__('Instagram'));
		}

		$customer = $this->checkCustomer($user_profile->identifier);
		if (!$customer || !$customer->getId()) {
			$name     = $user_profile->displayName;
			$arrName  = explode(' ', $name, 2);
			$user     = [
				'email'      => $user_profile->username . '@instagram.com',
				'firstname'  => isset($arrName[0]) ? $arrName[0] : $name,
				'lastname'   => isset($arrName[1]) ? $arrName[1] : $name,
				'identifier' => $user_profile->identifier
			];
			$customer = $this->createCustomer($user);
		}

		return $this->_appendJs($customer);
	}

}