<?php
/**
 * Copyright � 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mageplaza\SocialLogin\Model\System\Config\Source;

class Effect implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'mfp-zoom-in', 'label' => __('Zoom')],
            ['value' => 'mfp-newspaper', 'label' => __('Newspaper')],
            ['value' => 'mfp-move-horizontal', 'label' => __('Horizontal move')],
            ['value' => 'mfp-move-from-top', 'label' => __('Move from top')],
            ['value' => 'mfp-3d-unfold', 'label' => __('3D unfold')],
            ['value' => 'mfp-zoom-out', 'label' => __('Zoom-out')]
        ];
    }
}
