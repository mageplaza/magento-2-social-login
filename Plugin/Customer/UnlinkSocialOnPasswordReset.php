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

namespace Mageplaza\SocialLogin\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ForgotPasswordToken\ConfirmCustomerByToken;
use Mageplaza\SocialLogin\Model\ResourceModel\Social;

class UnlinkSocialOnPasswordReset
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Social
     */
    private $socialResource;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param Social $socialResource
     */
    public function __construct(CustomerRepositoryInterface $customerRepository, Social $socialResource)
    {
        $this->customerRepository = $customerRepository;
        $this->socialResource = $socialResource;
    }

    /**
     * @param AccountManagementInterface $subject
     * @param bool $result
     * @param string $email
     *
     * @return bool
     */
    public function afterResetPassword(AccountManagementInterface $subject, $result, $email)
    {
        if ($result) {
            $this->unlink((int)$this->customerRepository->get($email)->getId());
        }

        return $result;
    }

    /**
     * @param ConfirmCustomerByToken $subject
     * @param \Closure $proceed
     * @param int $customerId
     *
     * @return void
     */
    public function aroundResetCustomerConfirmation(ConfirmCustomerByToken $subject, \Closure $proceed, int $customerId)
    {
        $pending = (bool)$this->customerRepository->getById($customerId)->getConfirmation();

        $proceed($customerId);

        if ($pending) {
            $this->unlink($customerId);
        }
    }

    /**
     * @param int $customerId
     *
     * @return void
     */
    private function unlink(int $customerId)
    {
        $this->socialResource->getConnection()->delete(
            $this->socialResource->getMainTable(),
            ['customer_id = ?' => $customerId]
        );
    }
}
