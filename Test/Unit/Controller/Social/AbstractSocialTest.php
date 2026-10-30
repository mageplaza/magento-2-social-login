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
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\Store;
use Mageplaza\SocialLogin\Controller\Social\AbstractSocial;
use Mageplaza\SocialLogin\Controller\Social\Login;
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
        $this->apiObject = $this->getMockBuilder(Social::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerByEmail', 'setAuthorCustomer', 'createCustomerSocial', 'linkToCustomer'])
            ->getMock();
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->session        = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn', 'getCustomerId', 'getCustomer'])
            ->getMock();

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()->onlyMethods(['getWebsiteId'])->getMock();
        $store->method('getWebsiteId')->willReturn(1);

        $this->controller = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore', '_redirect'])
            ->getMock();
        $this->controller->method('getStore')->willReturn($store);

        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))->setValue($this->controller, $this->apiObject);
        (new ReflectionProperty(AbstractSocial::class, 'session'))->setValue($this->controller, $this->session);
        (new ReflectionProperty(\Magento\Framework\App\Action\Action::class, 'messageManager'))
            ->setValue($this->controller, $this->messageManager);
    }

    /**
     * @param int|null $id
     *
     * @return Customer|MockObject
     */
    private function customer($id)
    {
        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()->onlyMethods(['getId'])->getMock();
        $customer->method('getId')->willReturn($id);

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

    public static function emailVerifiedProvider(): array
    {
        return [
            'not set'      => [null],
            'false flag'   => [false],
            'true flag'    => [true],
            'true string'  => ['true'],
            'same email'   => ['jane@example.com'],
            'other case'   => ['Jane@Example.com'],
        ];
    }

    #[DataProvider('emailVerifiedProvider')]
    public function testGuestIsNeverLinkedToExistingAccountByEmail($emailVerified): void
    {
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

    public function testLoggedInOtherCustomerIsNotLinkedToExistingAccountByEmail(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(9);
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(7));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertFalse($this->controller->createCustomer($this->user(), 'Google'));
    }

    public function testLoggedInOwnerIsLinkedToTheirAccountByEmail(): void
    {
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
        $newCustomer = $this->customer(8);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(null));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->apiObject->expects($this->once())->method('createCustomerSocial')->willReturn($newCustomer);

        $this->assertSame($newCustomer, $this->controller->createCustomer($this->user(), 'Vkontakte'));
    }

    public function testLoggedInCustomerProcessLinksIdentityToCurrentCustomer(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->apiObject->expects($this->once())->method('linkToCustomer')->with('social-123', 7, 'Google');
        $this->apiObject->expects($this->never())->method('getCustomerByEmail');

        $profile             = new \Hybridauth\User\Profile();
        $profile->identifier = 'social-123';
        $profile->email      = 'someone-else@example.com';

        $this->controller->createCustomerProcess($profile, 'Google');
    }
}
