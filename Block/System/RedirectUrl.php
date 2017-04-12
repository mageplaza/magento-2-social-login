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
namespace Mageplaza\SocialLogin\Block\System;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field as FormField;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Magento\Backend\Block\Template\Context;

/**
 * Class Redirect
 * @package Mageplaza\SocialLogin\Block\System
 */
class RedirectUrl extends FormField
{
	/**
	 * @type \Mageplaza\SocialLogin\Helper\Social
	 */
	protected $socialHelper;

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		SocialHelper $socialHelper,
		array $data = []
	)
	{
		$this->socialHelper = $socialHelper;
		parent::__construct($context, $data);
	}

	/**
	 * @param AbstractElement $element
	 * @return string
	 */
	protected function _getElementHtml(AbstractElement $element)
	{
		$elementId   = explode('_', $element->getHtmlId());
		$redirectUrl = $this->socialHelper->getAuthUrl($elementId[1]);
		$html        = '<input style="opacity:1;" readonly id="' . $element->getHtmlId() . '" class="input-text admin__control-text" value="' . $redirectUrl . '" onclick="this.select()" type="text">';

		return $html;
	}
}
