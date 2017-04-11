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
namespace Mageplaza\SocialLogin\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Social
 * @package Mageplaza\SocialLogin\Model
 */
class Social extends AbstractModel
{
	/**
	 * @type \Magento\Store\Model\StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @type \Magento\Customer\Model\CustomerFactory
	 */
	protected $customerFactory;

	/**
	 * @type \Mageplaza\SocialLogin\Helper\Social
	 */
	protected $apiHelper;

	/**
	 * @type
	 */
	protected $apiName;

	/**
	 * @type array
	 */
	protected $apiData = [
		'Facebook'  => ["trustForwarded" => false, 'scope' => 'email, user_about_me'],
		'Twitter'   => ["includeEmail" => true],
		'LinkedIn'  => ["fields" => ['id', 'first-name', 'last-name', 'email-address']],
		'Vkontakte' => ['wrapper' => ['class' => '\Mageplaza\SocialLogin\Model\Providers\Vkontakte']],
		'Instagram' => ['wrapper' => ['class' => '\Mageplaza\SocialLogin\Model\Providers\Instagram']]
	];

	/**
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Customer\Model\CustomerFactory $customerFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Mageplaza\SocialLogin\Helper\Social $apiHelper
	 * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
	 * @param array $data
	 */
	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		CustomerFactory $customerFactory,
		StoreManagerInterface $storeManager,
		\Mageplaza\SocialLogin\Helper\Social $apiHelper,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	)
	{
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);

		$this->customerFactory = $customerFactory;
		$this->storeManager    = $storeManager;
		$this->apiHelper       = $apiHelper;
	}

	/**
	 * Define resource model
	 */
	protected function _construct()
	{
		$this->_init('Mageplaza\SocialLogin\Model\ResourceModel\Social');
	}

	/**
	 * @param $identify
	 * @param $type
	 * @return mixed
	 */
	public function getCustomerBySocial($identify, $type)
	{
		$customer = $this->customerFactory->create();

		$socialCustomer = $this->getCollection()
			->addFieldToFilter('social_id', $identify)
			->addFieldToFilter('type', $type)
			->getFirstItem();
		if ($socialCustomer && $socialCustomer->getId()) {
			$customer->load($socialCustomer->getCustomerId());
		}

		return $customer;
	}

	/**
	 * @param string $email
	 * @return bool|\Magento\Customer\Model\Customer
	 */
	public function getCustomerByEmail($email, $websiteId = null)
	{
		/** @var \Magento\Customer\Model\Customer $customer */
		$customer = $this->customerFactory->create();

		$customer->setWebsiteId($websiteId ?: $this->storeManager->getWebsite()->getId());
		$customer->loadByEmail($email);

		return $customer;
	}

	/**
	 * @param $data
	 * @param $store
	 * @return mixed
	 * @throws \Exception
	 */
	public function createCustomerSocial($data, $store)
	{
		$customer = $this->customerFactory->create();
		$customer->setFirstname($data['firstname'])
			->setLastname($data['lastname'])
			->setEmail($data['email'])
			->setStore($store);

		try {
			$customer->save();

			$this->setAuthorCustomer($data['identifier'], $customer->getId(), $data['type']);

			return $customer;
		} catch (\Exception $e) {
			if ($customer->getId()) {
				$customer->delete();
			}

			throw $e;
		}
	}

	/**
	 * @param $identifier
	 * @param $customerId
	 * @param $type
	 * @return $this
	 */
	public function setAuthorCustomer($identifier, $customerId, $type)
	{
		$this->setData([
			'social_id'              => $identifier,
			'customer_id'            => $customerId,
			'type'                   => $type,
			'is_send_password_email' => $this->apiHelper->canSendPassword()
		])
			->setId(null)
			->save();

		return $this;
	}

	/**
	 * @param $name
	 */
	protected function setApiName($name)
	{
		$this->apiName = $name;
		$this->apiHelper->correctXmlPath($name);
	}

	/**
	 * @param $apiName
	 * @return mixed
	 */
	public function getUserProfile($apiName)
	{
		$this->setApiName($apiName);
		$config = [
			"base_url"   => $this->apiHelper->getBaseAuthUrl(),
			"providers"  => [
				$apiName => $this->getProviderData()
			],
			"debug_mode" => false
		];

		try {
			$auth    = new \Hybrid_Auth($config);
			$adapter = $auth->authenticate($apiName, $this->getAdditionalParams());

			return $adapter->getUserProfile();
		} catch (\Exception $e) {
			echo __("Ooophs, we got an error: %1", $e->getMessage());
			die;
		}
	}

	/**
	 * @return array
	 */
	public function getProviderData()
	{
		$data = [
			"enabled" => $this->apiHelper->isEnabled(),
			"keys"    => [
				'id'     => $this->apiHelper->getAppId(),
				'key'    => $this->apiHelper->getAppId(),
				'secret' => $this->apiHelper->getAppSecret()
			]
		];

		if (isset($this->apiData[$this->apiName])) {
			$data = array_merge($data, $this->apiData[$this->apiName]);
		}

		return $data;
	}

	/**
	 * @return array|null
	 */
	protected function getAdditionalParams()
	{
		if ($this->apiName == 'OpenID') {
			return ["openid_identifier" => $this->apiHelper->getOpenIdIdentifier()];
		}

		return null;
	}
}