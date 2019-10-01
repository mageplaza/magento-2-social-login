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
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Block\Form;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionFactory;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Mageplaza\SocialLogin\Model\System\Config\Source\Information;

/**
 * Class Register
 *
 * @package Mageplaza\SocialLogin\Block\Form
 */
class Register extends \Magento\Customer\Block\Form\Register
{
    protected $_helperData;

    /**
     * Register constructor.
     *
     * @param Context $context
     * @param Data $directoryHelper
     * @param EncoderInterface $jsonEncoder
     * @param Config $configCacheType
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param Manager $moduleManager
     * @param Session $customerSession
     * @param Url $customerUrl
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $directoryHelper,
        EncoderInterface $jsonEncoder,
        Config $configCacheType,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        Manager $moduleManager,
        Session $customerSession,
        Url $customerUrl,
        HelperData $helperData,
        array $data = []
    ) {
        $this->_helperData = $helperData;

        parent::__construct($context, $directoryHelper, $jsonEncoder, $configCacheType, $regionFactory, $countryFactory, $moduleManager, $customerSession, $customerUrl, $data);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function checkFieldCanShow($storeId = null)
    {
        return $this->_helperData->getInfoRequire($storeId);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        return $this;
    }
}
