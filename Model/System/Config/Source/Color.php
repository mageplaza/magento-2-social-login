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
namespace Mageplaza\SocialLogin\Model\System\Config\Source;

/**
 * Class Color
 * @package Mageplaza\SocialLogin\Model\System\Config\Source
 */
class Color implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '#3399cc', 'label' => __('Default')],
            ['value' => 'orange', 'label' => __('Orange')],
            ['value' => 'green', 'label' => __('Green')],
            ['value' => 'black', 'label' => __('Black')],
            ['value' => 'blue', 'label' => __('Blue')],
            ['value' => 'darkblue', 'label' => __('Dark Blue')],
            ['value' => 'pink', 'label' => __('Pink')],
            ['value' => 'red', 'label' => __('Red')],
            ['value' => 'violet', 'label' => __('Violet')],
            ['value' => 'custom', 'label' => __('Custom')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            '#3399cc'  => __('Default'),
            'orange'   => __('Orange'),
            'green'    => __('Green'),
            'black'    => __('Black'),
            'blue'     => __('Blue'),
            'darkblue' => __('Dark Blue'),
            'pink'     => __('Pink'),
            'red'      => __('Red'),
            'violet'   => __('Violet'),
            'custom'   => __('Custom'),
        ];
    }
}
