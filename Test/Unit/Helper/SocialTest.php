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

use Mageplaza\SocialLogin\Helper\Social;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Social::class)]
class SocialTest extends TestCase
{
    /**
     * Build a Social helper with the heavy AbstractData constructor bypassed.
     *
     * @param string[] $onlyMethods
     * @return Social|MockObject
     */
    private function social(array $onlyMethods = [])
    {
        return $this->getMockBuilder(Social::class)
            ->disableOriginalConstructor()
            ->onlyMethods($onlyMethods)
            ->getMock();
    }

    /**
     * @param Social $social
     * @param string $type
     */
    private function setType(Social $social, string $type): void
    {
        $ref = new \ReflectionProperty(Social::class, '_type');
        $ref->setAccessible(true);
        $ref->setValue($social, $type);
    }

    public function testGetSocialTypesArrayListsAllProviders(): void
    {
        $types = $this->social()->getSocialTypesArray();

        $this->assertSame('Facebook', $types['facebook']);
        $this->assertSame('Zalo', $types['zalo']);
        $this->assertCount(12, $types);
    }

    public function testGetSocialConfigReturnsKnownProviderConfig(): void
    {
        $social = $this->social();

        $this->assertSame(
            ['trustForwarded' => false, 'scope' => 'email, public_profile'],
            $social->getSocialConfig('Facebook')
        );
        $this->assertSame(['scope' => 'email'], $social->getSocialConfig('Google'));
    }

    public function testGetSocialConfigReturnsEmptyForUnknownProvider(): void
    {
        $this->assertSame([], $this->social()->getSocialConfig('Unknown'));
    }

    public function testSetTypeValidTypeReturnsLabelAndStoresType(): void
    {
        $social = $this->social(['getConfigValue']);
        $social->method('getConfigValue')->willReturn(0);

        $this->assertSame('Facebook', $social->setType('facebook'));
        $this->assertSame('facebook', $social->getType());
    }

    public function testSetTypeInvalidTypeReturnsNull(): void
    {
        $social = $this->social(['getConfigValue']);
        $social->method('getConfigValue')->willReturn(0);

        $this->assertNull($social->setType('not_a_provider'));
        $this->assertNull($social->getType());
    }

    public function testSetTypeEmptyTypeReturnsNull(): void
    {
        $social = $this->social(['getConfigValue']);

        $this->assertNull($social->setType(''));
    }

    public function testGetSocialTypesIsSortedByConfiguredSortOrder(): void
    {
        $social = $this->social(['getConfigValue']);
        $social->method('getConfigValue')->willReturnCallback(static function ($path) {
            // Put google first, facebook last; everything else keeps insertion order (0).
            if ($path === 'sociallogin/google/sort_order') {
                return -10;
            }
            if ($path === 'sociallogin/facebook/sort_order') {
                return 100;
            }
            return 0;
        });

        $keys = array_keys($social->getSocialTypes());

        $this->assertSame('google', $keys[0]);
        $this->assertSame('facebook', end($keys));
    }

    public function testGetAppIdTrimsConfiguredValue(): void
    {
        $social = $this->social(['getConfigValue']);
        $this->setType($social, 'facebook');
        $social->method('getConfigValue')->with('sociallogin/facebook/app_id', null)->willReturn('  app-123  ');

        $this->assertSame('app-123', $social->getAppId());
    }

    public function testIsEnabledReadsProviderConfig(): void
    {
        $social = $this->social(['getConfigValue']);
        $this->setType($social, 'google');
        $social->method('getConfigValue')->with('sociallogin/google/is_enabled', null)->willReturn(true);

        $this->assertTrue($social->isEnabled());
    }

    public function testGetAuthUrlFacebookAppendsHauthDoneQueryParam(): void
    {
        $social = $this->social(['getBaseAuthUrl', 'setType']);
        $social->method('getBaseAuthUrl')->willReturn('https://shop/callback');
        $social->method('setType')->with('facebook')->willReturn('Facebook');

        $this->assertSame('https://shop/callback?hauth_done=Facebook', $social->getAuthUrl('facebook'));
    }

    public function testGetAuthUrlDefaultProviderAppendsHauthDoneParam(): void
    {
        $social = $this->social(['getBaseAuthUrl', 'setType']);
        $social->method('getBaseAuthUrl')->willReturn('https://shop/callback');
        $social->method('setType')->with('google')->willReturn('Google');

        $this->assertSame('https://shop/callback?hauth.done=Google', $social->getAuthUrl('google'));
    }

    public function testGetAuthUrlVkontakteReturnsBaseUrlUnchanged(): void
    {
        $social = $this->social(['getBaseAuthUrl', 'setType']);
        $social->method('getBaseAuthUrl')->willReturn('https://shop/callback');
        $social->method('setType')->with('vkontakte')->willReturn('Vkontakte');

        $this->assertSame('https://shop/callback', $social->getAuthUrl('vkontakte'));
    }

    public function testGetAuthUrlLiveConcatenatesLivePhp(): void
    {
        $social = $this->social(['getBaseAuthUrl', 'setType']);
        $social->method('getBaseAuthUrl')->willReturn('https://shop/callback/');
        $social->method('setType')->with('live')->willReturn('Live');

        $this->assertSame('https://shop/callback/live.php', $social->getAuthUrl('live'));
    }

    public function testGetDeleteDataUrlAppendsLowercasedType(): void
    {
        $social = $this->social(['getBaseDelete', 'setType']);
        $social->method('getBaseDelete')->willReturn('https://shop/datadeletion/');
        $social->method('setType')->with('facebook')->willReturn('Facebook');

        $this->assertSame('https://shop/datadeletion/type/Facebook', $social->getDeleteDataUrl('FaceBook'));
    }
}
