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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class DataDeletion
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class DataDeletion extends AbstractSocial
{

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws NoSuchEntityException
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
                    return $this->getResponse()->representJson('');
                }
            }
            $response = [
                'url'               => $this->getStore()->getBaseUrl() . "sociallogin/social/datadeletion/type/facebook?id={$data['user_id']}",
                'confirmation_code' => $data['user_id'],
            ];
            $response = json_encode($response, JSON_UNESCAPED_SLASHES);

            return $this->getResponse()->representJson($response);
        }
        if (isset($param['type']) && isset($param['id'])) {
            $paramsToDelete = [
                'id'   => $param['id'],
                'type' => $param['type'],
            ];
            $this->_forward('index', 'index', 'cms', $paramsToDelete);

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

}
