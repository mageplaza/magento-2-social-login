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
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

declare(strict_types=1);

namespace Mageplaza\SocialLogin\GraphQl\Model\Resolver;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mageplaza\SocialLogin\Block\Popup\Social;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

/**
 * Class SocialUrls
 * @package Mageplaza\SocialLogin\GraphQl\Model\Resolver
 */
class SocialUrls implements ResolverInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Social
     */
    protected $Social;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * SocialUrls constructor.
     *
     * @param HelperData $helperData
     * @param Social $Social
     * @param RequestInterface $request
     */
    public function __construct(
        HelperData $helperData,
        Social $Social,
        RequestInterface $request
    ) {
        $this->helperData = $helperData;
        $this->Social     = $Social;
        $this->request    = $request;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->helperData->isEnabled()) {
            throw new GraphQlNoSuchEntityException(__('Module is disabled.'));
        }

        $params = $this->request->getParams();
        $params = array_merge($params, $args);
        $this->request->setParams($params);

        $availableSocials = $this->Social->getAvailableSocials($args['storeId']);
        $items            = [];
        foreach ($availableSocials as $key => $social) {
            $items[] = [
                'social_type' => $key,
                'url'         => $social['login_url']
            ];
        }

        return [
            'items' => $items
        ];
    }
}
