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

namespace Mageplaza\SocialLogin\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class CsrfValidation
 * @package Mageplaza\SocialLogin\Plugin
 */
class CsrfValidator
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
     *
     * @return bool
     */
    public function aroundValidate(
        \Magento\Framework\App\Request\CsrfValidator $subject,
        callable $proceed,
        RequestInterface $request,
        ActionInterface $action
    ) {
        if ($request->getFullActionName() === 'sociallogin_social_datadeletion') {
            return true;
        }

        return $proceed($request, $action);
    }
}
