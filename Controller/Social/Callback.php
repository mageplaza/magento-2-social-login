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
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Controller\Social;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

/**
 * Class Callback
 *
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class Callback extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Callback constructor.
     * @param Context $context
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory
    )
    {
        parent::__construct($context);

        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        //Set Cancel url
        if (!isset($_GET['hauth_start'])) {
            if ($this->checkRequest('error_reason', 'user_denied')
                && $this->checkRequest('error', 'access_denied')
                && $this->checkRequest('error_code', '200')
                && $this->checkRequest('hauth_done', 'Facebook')
            ) {
                return $this->close();
            }
            if ($this->checkRequest('hauth_done', 'Twitter')
                && isset($_GET['denied'])
            ) {
                return $this->close();
            }
        }
        \Hybrid_Endpoint::process();
    }

    /**
     * @return $this
     */
    public function close()
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw->setContents(sprintf("<script>window.close();</script>"));
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function checkRequest($key, $value)
    {
        if (isset($_GET[$key]) && $_GET[$key] == $value) {
            return true;
        }

        return false;
    }
}