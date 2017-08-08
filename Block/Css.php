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
namespace Mageplaza\SocialLogin\Block;

use Mageplaza\SocialLogin\Helper\Data as DataHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;

/**
 * Class Css
 * @package Mageplaza\SocialLogin\Block
 */
class Css extends Template
{
    /**
     * @type \Mageplaza\SocialLogin\Helper\Social
     */
    protected $socialHelper;
	/**
	 * @type \Mageplaza\SocialLogin\Helper\Data
	 */
	protected $_helper;
    /**
     * @var ThemeProviderInterface
     */
    protected $_themeProviderInterface;
	/**
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Mageplaza\SocialLogin\Helper\Data $helper
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		DataHelper $helper,
        ThemeProviderInterface $themeProviderInterface,
        SocialHelper $socialHelper,
		array $data = []
	)
	{
		parent::__construct($context, $data);

        $this->_themeProviderInterface  = $themeProviderInterface;
        $this->socialHelper = $socialHelper;
		$this->_helper = $helper;
	}

	/**
	 * @return $this
	 */
	protected function _prepareLayout()
	{
		if ($this->_helper->isEnabled()) {
			if ($this->_helper->getGeneralConfig('popup_login')) {
				$this->pageConfig->addPageAsset('Mageplaza_SocialLogin::css/style.css');
				$this->pageConfig->addPageAsset('Mageplaza_Core::css/grid-mageplaza.css');
				$this->pageConfig->addPageAsset('Mageplaza_Core::css/font-awesome.min.css');
				$this->pageConfig->addPageAsset('Mageplaza_Core::css/magnific-popup.css');
			} elseif (in_array($this->_request->getFullActionName(), ['customer_account_login', 'customer_account_create'])) {
				$this->pageConfig->addPageAsset('Mageplaza_SocialLogin::css/style.css');
				$this->pageConfig->addPageAsset('Mageplaza_Core::css/font-awesome.min.css');
			}
		}

		return $this;
	}

	/**
	 * @return \Mageplaza\SocialLogin\Helper\Data
	 */
	public function helper()
	{
		return $this->_helper;
	}
    /**
     * @return string
     */
    public function getCurrentTheme(){
        return $this->_themeProviderInterface->getThemeById($this->socialHelper->getCurrentThemeId())->getCode();
    }
}
