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

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;

/**
 * Class Callback
 *
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class Callback extends AbstractSocial
{
    /**
     * @return ResponseInterface|Raw|ResultInterface|Callback|void
     *
     * @throws FailureToSendException
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute()
    {
        $param = $this->getRequest()->getParams();
        if (isset($param['live.php'])) {
            $param = array_merge($param, ['hauth_done' => 'Live']);
        }

        $type = $param['hauth_done'] ?? '';

        if ($this->checkRequest('hauth_start', false)
            && (($this->checkRequest('error_reason', 'user_denied')
                    && $this->checkRequest('error', 'access_denied')
                    && $this->checkRequest('error_code', '200')
                    && $this->checkRequest('hauth_done', 'Facebook'))
                || ($this->checkRequest('hauth_done', 'Twitter') && $this->checkRequest('denied')))
        ) {
            return $this->_appendJs(sprintf('<script>window.close();</script>'));
        }

        return $this->login($type);
    }
}
