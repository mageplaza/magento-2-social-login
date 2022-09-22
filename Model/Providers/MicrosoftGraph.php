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

use Hybridauth\Provider\MicrosoftGraph as MicrosoftGraphLib;

/**
 * Class MicrosoftGraph
 * @package Mageplaza\SocialLogin\Model\Providers
 */
class MicrosoftGraph extends MicrosoftGraphLib
{

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->config->set('tenant', 'consumers');

        parent::initialize();
    }
}
