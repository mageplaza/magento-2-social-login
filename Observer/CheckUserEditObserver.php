<?php

namespace Mageplaza\SocialLogin\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Mageplaza\SocialLogin\Model\ResourceModel\Social\CollectionFactory as SocialCollectionFactory;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Symfony\Component\Config\Definition\Exception\Exception;

class CheckUserEditObserver implements ObserverInterface
{
	/**
	 * @var \Magento\Captcha\Helper\Data
	 */
	protected $_helper;

	/**
	 * @var \Magento\Framework\App\ActionFlag
	 */
	protected $_actionFlag;

	/**
	 * @var \Magento\Framework\Message\ManagerInterface
	 */
	protected $messageManager;

	/**
	 * @var \Magento\Framework\Session\SessionManagerInterface
	 */
	protected $_session;


	/**
	 * Customer data
	 *
	 * @var \Magento\Customer\Model\Url
	 */
	protected $_customerUrl;
	protected $customerSession;
	protected $socialCollectionFactory;
	protected $socialHelper;

	public function __construct(
		HelperData $helper,
		\Magento\Framework\App\ActionFlag $actionFlag,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Framework\Session\SessionManagerInterface $session,
		\Magento\Customer\Model\Url $customerUrl,
		CustomerSession $customerSession,
		SocialCollectionFactory $socialCollectionFactory,
		SocialHelper $socialHelper
	)
	{
		$this->_helper                 = $helper;
		$this->_actionFlag             = $actionFlag;
		$this->messageManager          = $messageManager;
		$this->_session                = $session;
		$this->_customerUrl            = $customerUrl;
		$this->customerSession         = $customerSession;
		$this->socialCollectionFactory = $socialCollectionFactory;
		$this->socialHelper            = $socialHelper;
	}

	/**
	 * Check Captcha On User Login Page
	 *
	 * @param \Magento\Framework\Event\Observer $observer
	 * @return $this
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$event      = $observer->getEvent();
		$controller = $observer->getControllerAction();
		$editParams = $controller->getRequest()->getPost();
		$customerId = $this->customerSession->getId();
		$customer   = $this->_helper->getCustomerById($customerId);
		$isError    = $this->messageManager->getMessages()->getErrors();
		if (!count($isError) && $customer !== null) {
			$instagram = $this->getInstagramByCustomerId($customerId);
			$this->socialHelper->correctXmlPath('Instagram');
			if ($instagram != null && $this->socialHelper->sendPassword() == '1' && strpos($customer->getEmail(), '@instagram.com') !== false && $instagram->getData('is_send_password_email') == '0') {
				$customer->sendPasswordReminderEmail();
				$instagram->setIsSendPasswordEmail('1');
				try {
					$instagram->save();
				} catch (Exception $e) {
				}

				return $this;
			}

			$twitter = $this->getTwitterByCustomerId($customerId);
			$this->socialHelper->correctXmlPath('Twitter');
			if ($twitter != null && $this->socialHelper->sendPassword() == '1' && strpos($customer->getEmail(), '@twitter.com') !== false && $twitter->getData('is_send_password_email') == '0') {
				$customer->sendPasswordReminderEmail();
				$twitter->setIsSendPasswordEmail('1');
				try {
					$twitter->save();
				} catch (Exception $e) {
				}

				return $this;
			}
		}


		return $this;
	}

	public function getInstagramByCustomerId($customerId)
	{
		$instagram = $this->socialCollectionFactory->create();
		$user      = $instagram->addFieldToFilter('customer_id', $customerId)
			->getFirstItem();
		if ($user && $user->getId())
			return $user;
		else
			return null;
	}

	public function getTwitterByCustomerId($customerId)
	{
		$twitter = $this->socialCollectionFactory->create();
		$user    = $twitter->addFieldToFilter('customer_id', $customerId)
			->getFirstItem();
		if ($user && $user->getId())
			return $user;
		else
			return null;
	}

}