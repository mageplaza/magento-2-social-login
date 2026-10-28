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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BackfillSocialCustomerWebsiteIdTest extends TestCase
{
    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var BackfillSocialCustomerWebsiteId
     */
    private $patch;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);
        $setup            = $this->createMock(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($this->connection);
        $setup->method('getTable')->willReturnCallback(static function ($name) {
            return 'p_' . $name;
        });

        $this->patch = new BackfillSocialCustomerWebsiteId($setup, $this->logger);
    }

    public function testUpdatesOnlyNullRowsFromCustomerWebsite(): void
    {
        $this->connection->expects($this->once())->method('query')->with(
            $this->callback(static function ($sql) {
                return strpos($sql, 'UPDATE p_mageplaza_social_customer AS s') === 0
                    && strpos($sql, 'INNER JOIN p_customer_entity AS c ON c.entity_id = s.customer_id') !== false
                    && strpos($sql, 'SET s.website_id = c.website_id') !== false
                    && strpos($sql, 'WHERE s.website_id IS NULL') !== false;
            })
        );
        $this->connection->method('fetchOne')->willReturn('0');
        $this->logger->expects($this->never())->method('info');

        $this->assertSame($this->patch, $this->patch->apply());
    }

    public function testLogsRowsWithoutCustomer(): void
    {
        $this->connection->method('fetchOne')->willReturn('2');
        $this->logger->expects($this->once())->method('info')->with($this->stringContains('2 row(s)'));

        $this->patch->apply();
    }

    public function testNoDependenciesNoAliases(): void
    {
        $this->assertSame([], BackfillSocialCustomerWebsiteId::getDependencies());
        $this->assertSame([], $this->patch->getAliases());
    }
}
