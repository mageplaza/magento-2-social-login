<?php
/**
 * Copyright � 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mageplaza\SocialLogin\Block\System\Config\Form\Field\Instagram;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field as FormField;
use Mageplaza\SocialLogin\Helper\Instagram\Data as helperInstagram;
use Magento\Backend\Block\Template\Context;

/**
 * Backend system config datetime field renderer
 */
class Redirect extends FormField
{
    /**
     */
    protected $instagramHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(
        Context $context,
        helperInstagram $instagramHelper,
        array $data = []
    ) {
        $this->instagramHelper = $instagramHelper;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html_id     = $element->getHtmlId();
        $redirectUrl = $this->instagramHelper->getAuthUrl();
        $redirectUrl = str_replace('index.php/', '', $redirectUrl);
        $html        = '<input style="opacity:1;" readonly id="' . $html_id . '" class="input-text admin__control-text" value="' . $redirectUrl . '" onclick="this.select()" type="text">';

        return $html;
    }
}
