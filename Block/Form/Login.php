<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_SocialLogin
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Block\Form;

/**
 * Class Login
 * @package Mageplaza\SocialLogin\Block\Form
 */
class Login extends \Magento\Customer\Block\Form\Login
{
	/**
	 * @return $this
	 */
	protected function _prepareLayout()
	{
		return $this;
	}
}
