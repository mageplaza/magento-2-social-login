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

use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;
use Psr\Log\LoggerInterface;

/**
 * Class DataDeletion
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class DataDeletion extends AbstractSocial implements CsrfAwareActionInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * DataDeletion constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManager
     * @param SocialHelper $apiHelper
     * @param Social $apiObject
     * @param Session $customerSession
     * @param AccountRedirect $accountRedirect
     * @param RawFactory $resultRawFactory
     * @param Customer $customerModel
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManager,
        SocialHelper $apiHelper,
        Social $apiObject,
        Session $customerSession,
        AccountRedirect $accountRedirect,
        RawFactory $resultRawFactory,
        Customer $customerModel,
        TokenFactory $tokenFactory,
        LoggerInterface $logger
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
            $customerModel,
            $tokenFactory
        );
        $this->_logger = $logger;
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['type']) && $param['type'] === 'facebook' && isset($param['signed_request'])) {
            $signed_request = $param['signed_request'];
            $data           = $this->parseSignedRequest($signed_request);
            if ($data && $data['user_id']) {
                $this->apiObject->load($data['user_id'], 'social_id');
                try {
                    $this->apiObject->delete();
                } catch (Exception $e) {
                    $this->_logger->warning($e->getMessage());

                    return $this->getResponse()->representJson('');
                }
            }
            $confirmUrl = $this->_url->getUrl(
                'sociallogin/social/datadeletion/',
                ['type' => 'facebook', 'id' => $data['user_id']]
            );
            $response   = [
                'url'               => $confirmUrl,
                'confirmation_code' => $data['user_id'],
            ];
            $response   = json_encode($response, JSON_UNESCAPED_SLASHES);

            return $this->getResponse()->representJson($response);
        }
        if (isset($param['type']) && isset($param['id'])) {
            $paramsConfirm = [
                'id'   => $param['id'],
                'type' => $param['type'],
            ];
            $this->_forward('index', 'index', 'cms', $paramsConfirm);

            return;
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

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
