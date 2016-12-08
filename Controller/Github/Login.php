<?php
namespace Mageplaza\SocialLogin\Controller\Github;

use Mageplaza\SocialLogin\Controller\AbstractSocial;

class Login extends AbstractSocial
{
	protected $socialType = 'GitHub';

	public function execute()
	{
		$user_profile = $this->getUserProfile();
		if (!$user_profile->identifier) {
			return $this->emailRedirect(__('GitHub'));
		}

		$customer = $this->checkCustomer($user_profile->identifier);
		if (!$customer || !$customer->getId()) {
			$name    = $user_profile->displayName;
			$arrName = explode(' ', $name, 2);

			$email = $user_profile->email;
			if(!$email){
				$email = $user_profile->login . '@github.com';
			}

			$user    = [
				'email'      => $email,
				'firstname'  => isset($arrName[0]) ? $arrName[0] : $name,
				'lastname'   => isset($arrName[1]) ? $arrName[1] : $name,
				'identifier' => $user_profile->identifier
			];

			$customer = $this->createCustomer($user);
		}

		return $this->_appendJs($customer);
	}
}