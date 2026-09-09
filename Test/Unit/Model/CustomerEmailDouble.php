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

declare(strict_types=1);

namespace Mageplaza\SocialLogin\Test\Unit\Model;

use Magento\Customer\Model\Customer;

/**
 * Declared-method double: Customer::setWebsiteId() is a magic setter (no real method),
 * so it must be declared to be mockable under PHPUnit 12 (no addMethods()).
 */
abstract class CustomerEmailDouble extends Customer
{
    abstract public function setWebsiteId($websiteId);
}
