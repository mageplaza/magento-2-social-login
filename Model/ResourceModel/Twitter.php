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
namespace Mageplaza\SocialLogin\Model\ResourceModel;

class Twitter extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Date model
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */

    /**
     * constructor
     *
     * @param \Magento\Framework\Stdlib\DateTime\DateTime       $date
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }


    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mageplaza_twitter_customer', 'twitter_customer_id');
    }
}
