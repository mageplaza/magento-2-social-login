<?php

declare(strict_types=1);

namespace Mageplaza\SocialLogin\Test\Unit\Model\Providers;

use Mageplaza\SocialLogin\Model\Providers\Zalo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

require_once __DIR__ . '/CurlSetoptSpy.php';

#[CoversClass(Zalo::class)]
class ZaloTest extends TestCase
{
    private function provider(): Zalo
    {
        $provider = $this->getMockBuilder(Zalo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['storeData'])
            ->getMock();
        (new ReflectionProperty(Zalo::class, 'AuthorizeUrlParameters'))->setValue(
            $provider,
            ['client_id' => 'app-1', 'redirect_uri' => 'https://example.test/cb', 'response_type' => 'code']
        );
        (new ReflectionProperty(Zalo::class, 'AuthorizeUrlParametersEncType'))->setValue($provider, PHP_QUERY_RFC1738);

        return $provider;
    }

    private function authorizeUrl(Zalo $provider): string
    {
        return (new ReflectionMethod(Zalo::class, 'getAuthorizeUrl'))->invoke($provider);
    }

    private function queryOf(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    public function testAuthorizeUrlUsesAppIdAndCryptographicState(): void
    {
        $provider = $this->provider();
        $state    = null;
        $provider->expects($this->once())->method('storeData')
            ->willReturnCallback(static function ($name, $value) use (&$state) {
                $state = $value;
            });

        $url   = $this->authorizeUrl($provider);
        $query = $this->queryOf($url);

        $this->assertStringStartsWith('https://oauth.zaloapp.com/v4/permission?', $url);
        $this->assertSame('app-1', $query['app_id']);
        $this->assertArrayNotHasKey('client_id', $query);
        $this->assertMatchesRegularExpression('/^HA-[0-9a-f]{32}$/', $query['state']);
        $this->assertSame($state, $query['state']);
    }

    public function testAuthorizeUrlStateIsDifferentOnEveryCall(): void
    {
        $states = [];
        for ($i = 0; $i < 5; $i++) {
            $states[] = $this->queryOf($this->authorizeUrl($this->provider()))['state'];
        }

        $this->assertCount(5, array_unique($states));
    }

    public function testExchangeCodeVerifiesTlsPeerAndHost(): void
    {
        $provider = $this->provider();
        (new ReflectionProperty(Zalo::class, 'accessTokenUrl'))->setValue($provider, 'https://127.0.0.1:1/token');
        (new ReflectionProperty(Zalo::class, 'clientId'))->setValue($provider, 'app-1');
        (new ReflectionProperty(Zalo::class, 'clientSecret'))->setValue($provider, 'secret');
        $GLOBALS['mp_curl_setopt_calls'] = [];

        (new ReflectionMethod(Zalo::class, 'exchangeCodeForAccessToken'))->invoke($provider, 'auth-code');

        $this->assertTrue($GLOBALS['mp_curl_setopt_calls'][CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $GLOBALS['mp_curl_setopt_calls'][CURLOPT_SSL_VERIFYHOST]);
    }

    public function testExchangeCodeSendsSecretOnlyInHeaderAndParamsInBody(): void
    {
        $provider = $this->provider();
        (new ReflectionProperty(Zalo::class, 'accessTokenUrl'))->setValue($provider, 'https://127.0.0.1:1/token');
        (new ReflectionProperty(Zalo::class, 'clientId'))->setValue($provider, 'app-1');
        (new ReflectionProperty(Zalo::class, 'clientSecret'))->setValue($provider, 'secret');
        $GLOBALS['mp_curl_setopt_calls'] = [];

        (new ReflectionMethod(Zalo::class, 'exchangeCodeForAccessToken'))->invoke($provider, 'auth-code');

        $calls = $GLOBALS['mp_curl_setopt_calls'];
        $this->assertSame('https://127.0.0.1:1/token', $calls[CURLOPT_URL]);
        parse_str($calls[CURLOPT_POSTFIELDS], $body);
        $this->assertSame(['app_id' => 'app-1', 'grant_type' => 'authorization_code', 'code' => 'auth-code'], $body);
        $this->assertContains('secret_key: secret', $calls[CURLOPT_HTTPHEADER]);
    }
}
