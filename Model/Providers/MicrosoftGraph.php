<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2012 HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
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
