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

namespace Mageplaza\SocialLogin\Model\Providers;
/**
 * Class InstagramBusiness
 * @package Mageplaza\SocialLogin\Model\Providers
 */
class Facebook extends \Hybridauth\Provider\Facebook
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'email,public_profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.facebook.com/v19.0/';
}