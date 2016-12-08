<?php
namespace Mageplaza\SocialLogin\Controller\Yahoo;

use Mageplaza\SocialLogin\Controller\AbstractSocial;

class Login extends AbstractSocial
{
	protected $socialType = 'Yahoo';

	public function execute()
	{
		$user_profile = $this->getUserProfile();
		if (!$user_profile->email) {
			return $this->emailRedirect(__('Yahoo'));
		}

		$customer = $this->checkCustomer($user_profile->identifier);
		if (!$customer || !$customer->getId()) {
			$name     = $user_profile->displayName;
			$user     = [
				'email'      => $user_profile->email,
				'firstname'  => $user_profile->firstName ?: $name,
				'lastname'   => $user_profile->lastName ?: $name,
				'identifier' => $user_profile->identifier
			];
			$customer = $this->createCustomer($user);
		}

		return $this->_appendJs($customer);
	}
}