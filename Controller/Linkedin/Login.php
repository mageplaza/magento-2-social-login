<?php
namespace Mageplaza\SocialLogin\Controller\Linkedin;

use Mageplaza\SocialLogin\Controller\AbstractSocial;

class Login extends AbstractSocial
{
	protected $socialType = 'LinkedIn';

	public function execute()
	{
		$user_profile = $this->getUserProfile();
		if (!$user_profile->identifier) {
			return $this->emailRedirect(__('LinkedIn'));
		}

		$customer = $this->checkCustomer($user_profile->identifier);
		if (!$customer || !$customer->getId()) {
			$name     = $user_profile->displayName;
			$email = $user_profile->email;
			if(!$email){
				$email = $user_profile->identifier . '@linkedin.com';
			}
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