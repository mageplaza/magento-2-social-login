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

namespace Mageplaza\SocialLogin\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BackfillSocialCustomerWebsiteId
 * @package Mageplaza\SocialLogin\Setup\Patch\Data
 */
class BackfillSocialCustomerWebsiteId implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, LoggerInterface $logger)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger          = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $social     = $this->moduleDataSetup->getTable('mageplaza_social_customer');
        $customer   = $this->moduleDataSetup->getTable('customer_entity');

        $connection->query(
            "UPDATE {$social} AS s INNER JOIN {$customer} AS c ON c.entity_id = s.customer_id"
            . ' SET s.website_id = c.website_id'
            . ' WHERE s.website_id IS NULL AND c.website_id IS NOT NULL'
        );

        $remaining = (int) $connection->fetchOne("SELECT COUNT(*) FROM {$social} WHERE website_id IS NULL");
        if ($remaining) {
            $this->logger->info(
                "Mageplaza_SocialLogin: {$remaining} row(s) in mageplaza_social_customer keep website_id NULL"
                . ' (no matching customer).'
            );
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
