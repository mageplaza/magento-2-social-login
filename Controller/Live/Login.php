<?php
namespace Mageplaza\SocialLogin\Controller\Live;

use Mageplaza\SocialLogin\Controller\AbstractSocial;

class Login extends AbstractSocial
{
	protected $socialType = 'Live';

	public function execute()
	{
		$user_profile = $this->getUserProfile();
		if (!$user_profile->preferred_username) {
			return $this->emailRedirect(__('Microsoft Live'));
		}

		$customer = $this->checkCustomer($user_profile->sub);
		if (!$customer || !$customer->getId()) {
			$name     = $user_profile->name;
			$user     = [
				'email'      => $user_profile->preferred_username,
				'firstname'  => $user_profile->firstName ?: $name,
				'lastname'   => $user_profile->lastName ?: $name,
				'identifier' => $user_profile->sub
			];
			$customer = $this->createCustomer($user);
		}

		return $this->_appendJs($customer);
	}
}