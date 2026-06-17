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

use Mageplaza\SocialLogin\Model\System\Config\Source\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
class ColorTest extends TestCase
{
    /**
     * @var Color
     */
    private $source;

    protected function setUp(): void
    {
        $this->source = new Color();
    }

    public function testToArrayContainsDefaultAndCustomColors(): void
    {
        $colors = $this->source->toArray();

        $this->assertSame('Default', (string) $colors['#3399cc']);
        $this->assertSame('Custom', (string) $colors['custom']);
        $this->assertCount(11, $colors);
    }

    public function testToOptionArrayMapsEveryColorToValueLabelPair(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(11, $options);
        $this->assertSame('#3399cc', $options[0]['value']);
        $this->assertSame('Default', (string) $options[0]['label']);
    }
}
