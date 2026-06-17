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

use Mageplaza\SocialLogin\Model\System\Config\Source\Position;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Position::class)]
class PositionTest extends TestCase
{
    public function testToOptionArrayHasPlaceholderFollowedByAllPositions(): void
    {
        $options = (new Position())->toOptionArray();

        $this->assertCount(6, $options);
        $this->assertSame('', $options[0]['value']);
        $this->assertSame(
            [
                '',
                Position::PAGE_LOGIN,
                Position::PAGE_CREATE,
                Position::PAGE_FORGOT_PASS,
                Position::PAGE_POPUP,
                Position::PAGE_AUTHEN,
            ],
            array_column($options, 'value')
        );
        $this->assertSame('Customer Login Page', (string) $options[1]['label']);
    }
}
