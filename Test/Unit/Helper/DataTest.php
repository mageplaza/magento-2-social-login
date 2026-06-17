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

namespace Mageplaza\SocialLogin\Test\Unit\Helper;

use Mageplaza\SocialLogin\Helper\Data;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Data::class)]
class DataTest extends TestCase
{
    /**
     * @param string[] $onlyMethods
     * @return Data|MockObject
     */
    private function helper(array $onlyMethods = [])
    {
        return $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods($onlyMethods)
            ->getMock();
    }

    public function testGenerateBroadcastChannelScriptEmbedsEventAndJsonData(): void
    {
        $script = $this->helper()->generateBroadcastChannelScript('login', ['id' => 5, 'name' => 'Jane']);

        $this->assertStringContainsString("new BroadcastChannel('social-login-channel')", $script);
        $this->assertStringContainsString("event: 'login'", $script);
        $this->assertStringContainsString('data: {"id":5,"name":"Jane"}', $script);
        $this->assertStringContainsString('<script>', $script);
    }

    public function testIsEnabledGGRecaptchaTrueWhenGeneralAndFrontendEnabled(): void
    {
        $helper = $this->helper(['getConfigValue']);
        $helper->method('getConfigValue')->willReturnCallback(static function ($path) {
            return in_array($path, [
                'googlerecaptcha/general/enabled',
                'googlerecaptcha/frontend/enabled',
            ], true);
        });

        $this->assertTrue((bool) $helper->isEnabledGGRecaptcha());
    }

    public function testIsEnabledGGRecaptchaFalseWhenFrontendDisabled(): void
    {
        $helper = $this->helper(['getConfigValue']);
        $helper->method('getConfigValue')->willReturnCallback(static function ($path) {
            return $path === 'googlerecaptcha/general/enabled'; // frontend returns false
        });

        $this->assertFalse((bool) $helper->isEnabledGGRecaptcha());
    }
}
