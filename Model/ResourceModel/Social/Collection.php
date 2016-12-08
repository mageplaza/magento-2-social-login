<?php

namespace Mageplaza\SocialLogin\Model\ResourceModel\Social;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Mageplaza\SocialLogin\Model\Social',
            'Mageplaza\SocialLogin\Model\ResourceModel\Social'
        );
    }
}