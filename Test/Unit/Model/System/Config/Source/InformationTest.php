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

namespace Mageplaza\SocialLogin\Test\Unit\Model\System\Config\Source;

use Mageplaza\SocialLogin\Model\System\Config\Source\Information;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Information::class)]
class InformationTest extends TestCase
{
    public function testToOptionArrayExposesEmailNamePassword(): void
    {
        $options = (new Information())->toOptionArray();

        $this->assertSame(
            [Information::INFO_EMAIL, Information::INFOR_NAME, Information::INFOR_PW],
            array_column($options, 'value')
        );
        $this->assertSame('Email', (string) $options[0]['label']);
        $this->assertSame('Password', (string) $options[2]['label']);
    }
}
