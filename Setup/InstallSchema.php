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
 * @category    Mageplaza
 * @package     Mageplaza_SocialLogin
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\SocialLogin\Setup;

/**
 * Class InstallSchema
 * @package Mageplaza\SocialLogin\Setup
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * install tables
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (!$installer->tableExists('mageplaza_social_customer')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('mageplaza_social_customer')
            )
                ->addColumn(
                    'social_customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 11,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Social Customer ID'
                )
                ->addColumn(
                    'social_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, ['unsigned' => true, 'nullable => false'], 'Social Id'
                )->addColumn(
                    'customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10, ['unsigned' => true, 'nullable => false'], 'Customer Id'
                )->addColumn(
                    'is_send_password_email', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10, ['unsigned' => true, 'nullable => false', 'default' => '0'], 'Is Send Password Email'
                )->addColumn(
                    'type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, ['default' => ''], 'Type'
                )->addForeignKey(
                    $installer->getFkName('mageplaza_social_customer', 'customer_id', 'customer_entity', 'entity_id'),
                    'customer_id',
                    $installer->getTable('customer_entity'),
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )->setComment('Social Customer Table');

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
