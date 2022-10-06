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
 * @category  Mageplaza
 * @package   Mageplaza_SocialLogin
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Controller\Social;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;

/**
 * Class DataDeletion
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class DataDeletion extends AbstractSocial
{
    /**
     * @type \Mageplaza\SocialLogin\Helper\Social
     */
    protected $apiHelper;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManager,
        SocialHelper $apiHelper,
        Social $apiObject,
        Session $customerSession,
        AccountRedirect $accountRedirect,
        RawFactory $resultRawFactory,
        Customer $customerModel
    ) {
        parent::__construct(
            $context,
            $storeManager,
            $accountManager,
            $apiHelper,
            $apiObject,
            $customerSession,
            $accountRedirect,
            $resultRawFactory,
            $customerModel
        );

    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Log_Exception
     */
    public function execute()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['type']) && $param['type'] === 'facebook') {
            $signed_request = $param['signed_request'];
            $data           = $this->parseSignedRequest($signed_request);
            if ($data && $data['user_id']) {
                $this->apiObject->load($data['user_id'], 'social_id');
                try {
                    $this->apiObject->delete();
                } catch (\Exception $e) {
                    return $this->getResponse()->representJson('');
                }
            }
            $response = [
                'url'               => $this->getStore()->getBaseUrl() . "/sociallogin/social/datadeletion/type/facebook?id={$data['algorithm']}",
                'confirmation_code' => $data['algorithm'],
            ];
            $response = AbstractData::jsonEncode($response);

            return $this->getResponse()->representJson($response);
        }

        return $this->getResponse()->representJson('');

    }

    /**
     * @param string $signedRequest
     *
     * @return mixed|null
     */
    public function parseSignedRequest($signedRequest)
    {
        [$encoded_sig, $payload] = explode('.', $signedRequest, 2);

        $this->apiHelper->setType('facebook');
        $secret       = $this->apiHelper->getAppSecret();
        $sig          = $this->base64UrlDecode($encoded_sig);
        $data         = json_decode($this->base64UrlDecode($payload), true);
        $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
        if ($sig !== $expected_sig) {
            return null;
        }

        return $data;
    }

    /**
     * @param $input
     *
     * @return false|string
     */
    public function base64UrlDecode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
