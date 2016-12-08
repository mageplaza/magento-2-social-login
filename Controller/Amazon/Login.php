<?php
namespace Mageplaza\SocialLogin\Controller\Amazon;

use Mageplaza\SocialLogin\Controller\AbstractSocial;

class Login extends AbstractSocial
{
	protected $socialType = 'Amazon';

	public function execute()
	{
		$result = $this->curlConnect();
		if (!$result->email) {
			return $this->emailRedirect(__('Amazon'));
		}

		$customer = $this->checkCustomer($result->identifier);
		if (!$customer || !$customer->getId()) {
			$name    = $result->name;
			$arrName = explode(' ', $name, 2);
			$user    = [
				'email'      => $result->email,
				'firstname'  => isset($arrName[0]) ? $arrName[0] : $name,
				'lastname'   => isset($arrName[1]) ? $arrName[1] : $name,
				'identifier' => $result->user_id
			];

			$customer = $this->createCustomer($user);
		}

		if ($customer && $customer->getId()) {
			$this->session->setCustomerAsLoggedIn($customer);
		}

		$this->_redirect('customer/account');

		return $this;
	}

	/**
	 * todo: Check this connect function
	 *
	 * @return mixed
	 */
	private function curlConnect()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, 'https://api.amazon.com/auth/o2/tokeninfo?access_token=' . urlencode($_REQUEST['access_token']));
		$result = curl_exec($ch);
		curl_close($ch);
		$d = json_decode($result, true);

		$c = curl_init('https://api.amazon.com/user/profile');
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $_REQUEST['access_token']));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

		$r = curl_exec($c);
		curl_close($c);

		return json_decode($r);
	}
}