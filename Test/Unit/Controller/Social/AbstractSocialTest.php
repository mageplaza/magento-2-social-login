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

    protected function setUp(): void
    {
        $this->apiObject = $this->getMockBuilder(Social::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerByEmail', 'setAuthorCustomer', 'createCustomerSocial'])
            ->getMock();
        $this->messageManager = $this->createMock(ManagerInterface::class);

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()->onlyMethods(['getWebsiteId'])->getMock();
        $store->method('getWebsiteId')->willReturn(1);

        $this->controller = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->getMock();
        $this->controller->method('getStore')->willReturn($store);

        (new ReflectionProperty(AbstractSocial::class, 'apiObject'))->setValue($this->controller, $this->apiObject);
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

    private function user($emailVerified): array
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
            'not set'             => [null],
            'empty string'        => [''],
            'false flag'          => [false],
            'false string'        => ['false'],
            'other email address' => ['someone-else@example.com'],
        ];
    }

    #[DataProvider('unverifiedProvider')]
    public function testExistingAccountIsNotLinkedWhenEmailIsNotVerified($emailVerified): void
    {
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(7));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->apiObject->expects($this->never())->method('createCustomerSocial');
        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertFalse($this->controller->createCustomer($this->user($emailVerified), 'Google'));
    }

    public static function verifiedProvider(): array
    {
        return [
            'same email'               => ['jane@example.com'],
            'same email, other case'   => ['Jane@Example.com'],
            'boolean flag'             => [true],
            'string flag'              => ['true'],
        ];
    }

    #[DataProvider('verifiedProvider')]
    public function testExistingAccountIsLinkedWhenEmailIsVerified($emailVerified): void
    {
        $customer = $this->customer(7);
        $this->apiObject->method('getCustomerByEmail')->willReturn($customer);
        $this->apiObject->expects($this->once())->method('setAuthorCustomer')->with('social-123', 7, 'Google');
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame($customer, $this->controller->createCustomer($this->user($emailVerified), 'Google'));
    }

    public function testNewAccountIsCreatedWithoutVerifiedEmail(): void
    {
        $newCustomer = $this->customer(8);
        $this->apiObject->method('getCustomerByEmail')->willReturn($this->customer(null));
        $this->apiObject->expects($this->never())->method('setAuthorCustomer');
        $this->apiObject->expects($this->once())->method('createCustomerSocial')->willReturn($newCustomer);

        $this->assertSame($newCustomer, $this->controller->createCustomer($this->user(null), 'Vkontakte'));
    }

    public function testCreateCustomerProcessPassesEmailVerifiedFlag(): void
    {
        $controller = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createCustomer'])
            ->getMock();
        (new ReflectionProperty(\Magento\Framework\App\Action\AbstractAction::class, '_request'))
            ->setValue($controller, $this->createMock(\Magento\Framework\App\RequestInterface::class));

        $profile                = new \Hybridauth\User\Profile();
        $profile->identifier    = 'social-123';
        $profile->email         = 'jane@example.com';
        $profile->emailVerified = 'jane@example.com';
        $profile->displayName   = 'Jane Doe';

        $controller->expects($this->once())->method('createCustomer')
            ->with($this->callback(static function ($user) {
                return $user['email_verified'] === 'jane@example.com' && $user['email'] === 'jane@example.com';
            }), 'Google')
            ->willReturn(false);

        $controller->createCustomerProcess($profile, 'Google');
    }
}
