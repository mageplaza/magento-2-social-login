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

namespace Mageplaza\SocialLogin\Test\Unit\Controller\Social;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Mageplaza\SocialLogin\Controller\Social\AbstractSocial;
use Mageplaza\SocialLogin\Controller\Social\Login;
use Mageplaza\SocialLogin\Helper\Data as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(AbstractSocial::class)]
class AbstractSocialTest extends TestCase
{
    /**
     * @var Social|MockObject
     */
    private $apiObject;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Login|MockObject
     */
    private $controller;

    /**
     * @var Session|MockObject
     */
    private $session;

    protected function setUp(): void
    {
        $this->apiObject = $this->createStub(Social::class);
        $this->messageManager = $this->createStub(ManagerInterface::class);
        $this->session = $this->createStub(Session::class);
        $store = $this->createStub(Store::class);
        $store->method('getWebsiteId')->willReturn(1);
        $storeManager = $this->createStub(\Magento\Store\Model\StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $this->controller = (new \ReflectionClass(Login::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(AbstractSocial::class, 'storeManager'))->setValue($this->controller, $storeManager);

        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))->setValue($this->controller, $this->apiObject);
        (new ReflectionProperty(AbstractSocial::class, 'session'))->setValue($this->controller, $this->session);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
    }

    /**
     * @param int|null $id
     * @param string|null $confirmation
     *
     * @return Customer|MockObject
     */
    private function customer($id, $confirmation = null)
    {
        $customer = $this->createStub(Customer::class);
        $customer->method('getId')->willReturn($id);
        $customer->method('getData')->willReturn($confirmation);

        return $customer;
    }

    private function user($emailVerified = null): array
    {
        return [
            'email'          => 'jane@example.com',
            'identifier'     => 'social-123',
            'email_verified' => $emailVerified,
            'type'           => 'Google',
        ];
    }

    public static function unverifiedProvider(): array
    {
        return [
            'not set'     => [null],
            'empty'       => [''],
            'false flag'  => [false],
            'true flag'   => [true],
            'other email' => ['other@example.com'],
        ];
    }

    /**
     * @dataProvider unverifiedProvider
     */
    #[DataProvider('unverifiedProvider')]
    public function testGuestWithUnverifiedEmailIsNotLinkedToExistingAccount($emailVerified): void
    {
        $this->apiObject = $this->createMock(Social::class);
        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))
            ->setValue($this->controller, $this->apiObject);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(7));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->apiObject->expects($this->never())->method('createCustomerSocial');
        $this->messageManager->expects($this->once())->method('addErrorMessage')
            ->with($this->callback(static function ($message) {
                return (string) $message
                    === 'An account with this email already exists. Please sign in with your password first.';
            }));

        $this->assertFalse($this->controller->createCustomer($this->user($emailVerified), 'Google'));
    }

    public static function verifiedProvider(): array
    {
        return [
            'same email' => ['jane@example.com'],
            'other case' => ['Jane@Example.com'],
            'padded'     => [' jane@example.com '],
        ];
    }

    /**
     * @dataProvider verifiedProvider
     */
    #[DataProvider('verifiedProvider')]
    public function testGuestWithProviderVerifiedEmailIsLinkedAndLoggedIn($emailVerified): void
    {
        $this->apiObject = $this->createMock(Social::class);
        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))
            ->setValue($this->controller, $this->apiObject);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
        $customer = $this->customer(7);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->apiObject->method('getCustomerByEmail')->willReturn($customer);
        $this->apiObject->expects($this->once())->method('setAuthorCustomer')->with('social-123', 7, 'Google');
        $this->apiObject->expects($this->never())->method('createCustomerSocial');
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame($customer, $this->controller->createCustomer($this->user($emailVerified), 'Google'));
    }

    public function testGuestWithVerifiedEmailIsBlockedWhenCustomerAwaitsConfirmation(): void
    {
        $this->apiObject = $this->createMock(Social::class);
        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))
            ->setValue($this->controller, $this->apiObject);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(7, 'abc123'));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertFalse($this->controller->createCustomer($this->user('jane@example.com'), 'Google'));
    }

    public function testLoggedInOtherCustomerIsNotLinkedToExistingAccountByEmail(): void
    {
        $this->apiObject = $this->createMock(Social::class);
        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))
            ->setValue($this->controller, $this->apiObject);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(9);
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(7));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertFalse($this->controller->createCustomer($this->user(), 'Google'));
    }

    public function testLoggedInOwnerIsLinkedToTheirAccountByEmail(): void
    {
        $this->apiObject = $this->createMock(Social::class);
        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))
            ->setValue($this->controller, $this->apiObject);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
        $customer = $this->customer(7);
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->apiObject->method('getCustomerByEmail')->willReturn($customer);
        $this->apiObject->expects($this->once())->method('setAuthorCustomer')->with('social-123', 7, 'Google');
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame($customer, $this->controller->createCustomer($this->user(), 'Google'));
    }

    public function testNewAccountIsCreatedForGuestWithUnusedEmail(): void
    {
        $this->apiObject = $this->createMock(Social::class);
        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))
            ->setValue($this->controller, $this->apiObject);
        $newCustomer = $this->customer(8);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(null));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->apiObject->expects($this->once())->method('createCustomerSocial')->willReturn($newCustomer);

        $this->assertSame($newCustomer, $this->controller->createCustomer($this->user(), 'Vkontakte'));
    }

    /**
     * @param string|null $realEmail
     *
     * @return Login|MockObject
     */
    private function controllerWithRealEmail($realEmail)
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key) => $key === 'realEmail' ? $realEmail : null
        );
        $store = $this->createStub(Store::class);
        $controller = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore', 'createCustomer'])
            ->getMock();
        $controller->method('getStore')->willReturn($store);
        (new ReflectionProperty(AbstractAction::class, '_request'))->setValue($controller, $request);
        (new ReflectionProperty(AbstractSocial::class, 'session'))->setValue($controller, $this->session);

        return $controller;
    }

    private function profileWithVerifiedEmail(): \Hybridauth\User\Profile
    {
        $profile                = new \Hybridauth\User\Profile();
        $profile->identifier    = 'social-123';
        $profile->email         = 'typed@example.com';
        $profile->emailVerified = 'typed@example.com';
        $profile->displayName   = 'Jane Doe';

        return $profile;
    }

    public function testRealEmailNeverCarriesProviderVerifiedFlagAndRequiresConfirmation(): void
    {
        $this->session->method('isLoggedIn')->willReturn(false);
        $controller = $this->controllerWithRealEmail('typed@example.com');
        $controller->expects($this->once())->method('createCustomer')->with(
            $this->callback(static function ($user) {
                return $user['email_verified'] === '' && $user['confirm'] === true;
            }),
            'Google'
        )->willReturn($this->customer(8));

        $controller->createCustomerProcess($this->profileWithVerifiedEmail(), 'Google');
    }

    public function testProviderVerifiedFlagIsKeptWhenEmailComesFromProvider(): void
    {
        $this->session->method('isLoggedIn')->willReturn(false);
        $controller = $this->controllerWithRealEmail(null);
        $controller->expects($this->once())->method('createCustomer')->with(
            $this->callback(static function ($user) {
                return $user['email_verified'] === 'typed@example.com' && $user['confirm'] === false;
            }),
            'Google'
        )->willReturn($this->customer(8));

        $controller->createCustomerProcess($this->profileWithVerifiedEmail(), 'Google');
    }

    public function testLoggedInCustomerProcessLinksIdentityToCurrentCustomer(): void
    {
        $this->apiObject = $this->createMock(Social::class);
        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))
            ->setValue($this->controller, $this->apiObject);
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->apiObject->expects($this->once())->method('linkToCustomer')->with('social-123', 7, 'Google');
        $this->apiObject->expects($this->never())->method('getCustomerByEmail');

        $profile             = new \Hybridauth\User\Profile();
        $profile->identifier = 'social-123';
        $profile->email      = 'someone-else@example.com';

        $this->controller->createCustomerProcess($profile, 'Google');
    }

    public function testEmailRedirectOnlyAddsMessageAndDoesNotRedirectThePopup(): void
    {
        $this->controller = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()->onlyMethods(['_redirect'])->getMock();
        $this->messageManager = $this->createMock(ManagerInterface::class);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
        $this->controller->expects($this->never())->method('_redirect');
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with('Refused');

        $this->assertSame($this->controller, $this->controller->emailRedirect('Refused', false));
    }

    public function testLoginRefusedResultClosesPopupAndSendsOpenerToLoginPage(): void
    {
        $url = $this->createMock(UrlInterface::class);
        $url->expects($this->once())->method('getUrl')->with('customer/account/login')->willReturn('https://shop.test/customer/account/login');
        $helper = $this->getMockBuilder(SocialHelper::class)
            ->disableOriginalConstructor()->onlyMethods(['generateBroadcastChannelScript'])->getMock();
        $helper->expects($this->once())->method('generateBroadcastChannelScript')
            ->with('loginRedirect', ['redirectUrl' => 'https://shop.test/customer/account/login'])
            ->willReturn('<script>broadcast</script>');
        $raw = $this->getMockBuilder(Raw::class)->disableOriginalConstructor()->onlyMethods(['setContents'])->getMock();
        $raw->expects($this->once())->method('setContents')
            ->with('<script>broadcast</script><script>window.close();</script>')->willReturnSelf();
        $factory = $this->createMock(RawFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($raw);

        (new ReflectionProperty(AbstractSocial::class, 'apiHelper'))->setValue($this->controller, $helper);
        (new ReflectionProperty(AbstractSocial::class, 'resultRawFactory'))->setValue($this->controller, $factory);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, '_url'))
            ->setValue($this->controller, $url);

        $this->assertSame($raw, $this->controller->loginRefusedResult());
    }
}
