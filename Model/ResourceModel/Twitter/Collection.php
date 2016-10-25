<?php
/**
 * Mageplaza_SocialLogin extension
 *                     NOTICE OF LICENSE
 *
 *                     This source file is subject to the MIT License
 *                     that is bundled with this package in the file LICENSE.txt.
 *                     It is also available through the world-wide-web at this URL:
 *                     http://opensource.org/licenses/mit-license.php
 *
 * @category  Mageplaza
 * @package   Mageplaza_SocialLogin
 * @copyright Copyright (c) 2016
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Mageplaza\SocialLogin\Model\ResourceModel\Twitter;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Mageplaza\SocialLogin\Model\Twitter\Customer', 'Mageplaza\SocialLogin\Model\ResourceModel\Twitter');
    }

    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Zend_Db_Select::GROUP);

        return $countSelect;
    }

}
