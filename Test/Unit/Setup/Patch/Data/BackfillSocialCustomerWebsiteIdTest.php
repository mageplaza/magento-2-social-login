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

namespace Mageplaza\SocialLogin\Test\Unit\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Mageplaza\SocialLogin\Setup\Patch\Data\BackfillSocialCustomerWebsiteId;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BackfillSocialCustomerWebsiteIdTest extends TestCase
{
    private function patch(AdapterInterface $connection, LoggerInterface $logger): BackfillSocialCustomerWebsiteId
    {
        $setup = $this->createStub(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturnCallback(static function ($name) {
            return 'p_' . $name;
        });

        return new BackfillSocialCustomerWebsiteId($setup, $logger);
    }

    public function testUpdatesOnlyNullRowsFromCustomerWebsite(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $patch = $this->patch($connection, $logger);
        $connection->expects($this->once())->method('query')->with(
            $this->callback(static function ($sql) {
                return strpos($sql, 'UPDATE p_mageplaza_social_customer AS s') === 0
                    && strpos($sql, 'INNER JOIN p_customer_entity AS c ON c.entity_id = s.customer_id') !== false
                    && strpos($sql, 'SET s.website_id = c.website_id') !== false
                    && strpos($sql, 'WHERE s.website_id IS NULL') !== false;
            })
        );
        $connection->method('fetchOne')->willReturn('0');
        $logger->expects($this->never())->method('info');

        $this->assertSame($patch, $patch->apply());
    }

    public function testLogsRowsWithoutCustomer(): void
    {
        $connection = $this->createStub(AdapterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $patch = $this->patch($connection, $logger);
        $connection->method('fetchOne')->willReturn('2');
        $logger->expects($this->once())->method('info')->with($this->stringContains('2 row(s)'));

        $patch->apply();
    }

    public function testNoDependenciesNoAliases(): void
    {
        $patch = $this->patch(
            $this->createStub(AdapterInterface::class),
            $this->createStub(LoggerInterface::class)
        );
        $this->assertSame([], BackfillSocialCustomerWebsiteId::getDependencies());
        $this->assertSame([], $patch->getAliases());
    }
}
