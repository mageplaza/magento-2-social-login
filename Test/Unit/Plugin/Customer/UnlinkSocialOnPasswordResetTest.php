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

namespace Mageplaza\SocialLogin\Test\Unit\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ForgotPasswordToken\ConfirmCustomerByToken;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Mageplaza\SocialLogin\Model\ResourceModel\Social;
use Mageplaza\SocialLogin\Plugin\Customer\UnlinkSocialOnPasswordReset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnlinkSocialOnPasswordResetTest extends TestCase
{
    public function testDeletesLinksOfResetCustomer()
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getId')->willReturn('7');
        $repository = $this->createMock(CustomerRepositoryInterface::class);
        $repository->expects($this->once())->method('get')->with('victim@example.com')->willReturn($customer);
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->once())->method('delete')
            ->with('mageplaza_social_customer', ['customer_id = ?' => 7]);
        $resource = $this->createMock(Social::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('mageplaza_social_customer');

        $plugin = new UnlinkSocialOnPasswordReset($repository, $resource);

        $this->assertTrue(
            $plugin->afterResetPassword(
                $this->createMock(AccountManagementInterface::class),
                true,
                'victim@example.com'
            )
        );
    }

    public function testKeepsLinksWhenResetDidNotSucceed()
    {
        $repository = $this->createMock(CustomerRepositoryInterface::class);
        $repository->expects($this->never())->method('get');
        $resource = $this->createMock(Social::class);
        $resource->expects($this->never())->method('getConnection');

        $plugin = new UnlinkSocialOnPasswordReset($repository, $resource);

        $this->assertFalse(
            $plugin->afterResetPassword($this->createMock(AccountManagementInterface::class), false, 'a@b.c')
        );
    }

    #[DataProvider('confirmationProvider')]
    public function testResetLinkUnlinksOnlyPendingCustomer($confirmation, $expectedDeletes)
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getConfirmation')->willReturn($confirmation);
        $repository = $this->createMock(CustomerRepositoryInterface::class);
        $repository->method('getById')->with(7)->willReturn($customer);
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->exactly($expectedDeletes))->method('delete')
            ->with('mageplaza_social_customer', ['customer_id = ?' => 7]);
        $resource = $this->createMock(Social::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('mageplaza_social_customer');
        $called = 0;

        $plugin = new UnlinkSocialOnPasswordReset($repository, $resource);
        $plugin->aroundResetCustomerConfirmation(
            $this->createMock(ConfirmCustomerByToken::class),
            function ($id) use (&$called) {
                $called = $id;
            },
            7
        );

        $this->assertSame(7, $called);
    }

    public static function confirmationProvider()
    {
        return [
            'pending' => ['abc123', 1],
            'active'  => [null, 0],
        ];
    }
}
