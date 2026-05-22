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

namespace Mageplaza\SocialLogin\Plugin\Captcha;

use Magento\Customer\Controller\Ajax\Login;
use Magento\Framework\Serialize\Serializer\Json;

class FixNullFormId
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(Json $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param Login $subject
     * @param \Closure $proceed
     *
     * @return mixed
     */
    public function aroundExecute(Login $subject, \Closure $proceed)
    {
        $request = $subject->getRequest();
        $content = $request->getContent();

        if ($content) {
            try {
                $loginParams = $this->serializer->unserialize($content);
                if (is_array($loginParams) && !isset($loginParams['captcha_form_id'])) {
                    $loginParams['captcha_form_id'] = 'user_login';
                    $request->setContent($this->serializer->serialize($loginParams));
                }
            } catch (\Exception $e) {
                // skip
            }
        }

        return $proceed();
    }
}
