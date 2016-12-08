<?php
namespace Mageplaza\SocialLogin\Controller\Twitter;

use Mageplaza\SocialLogin\Controller\AbstractSocial;

class Login extends AbstractSocial
{
	protected $socialType = 'Twitter';

	public function execute()
	{
		$user_profile = $this->getUserProfile();
		if (!$user_profile->displayName) {
			return $this->emailRedirect(__('Twitter'));
		}

		$customer = $this->checkCustomer($user_profile->identifier);
		if (!$customer || !$customer->getId()) {
			$name     = $user_profile->displayName;
			$email    = $name . '@twitter.com';
			$user     = [
				'email'      => $email,
				'firstname'  => $user_profile->firstName ?: $name,
				'lastname'   => $user_profile->lastName ?: $name,
				'identifier' => $user_profile->identifier
			];
			$customer = $this->createCustomer($user);
		}

		return $this->_appendJs($customer);
	}
}