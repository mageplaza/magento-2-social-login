<?php
namespace Mageplaza\SocialLogin\Model;

use Magento\Framework\Model\AbstractModel;

class Social extends AbstractModel
{
	protected $storeManager;
	protected $objectManager;
	protected $apiHelper;

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\ObjectManagerInterface $objectmanager,
		\Mageplaza\SocialLogin\Helper\Social $apiHelper,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	)
	{
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);

		$this->apiHelper     = $apiHelper;
		$this->storeManager  = $storeManager;
		$this->objectManager = $objectmanager;
	}

	/**
	 * Define resource model
	 */
	protected function _construct()
	{
		$this->_init('Mageplaza\SocialLogin\Model\ResourceModel\Social');
	}

	public function getAuth($apiName)
	{
		$this->apiHelper->correctXmlPath($apiName);
		$config = [
			"base_url"  => $this->apiHelper->getBaseAuthUrl(),
			"providers" => [
				$apiName => $this->getProviderData()
			]
		];

		try {
			$auth = $this->objectManager->create('Mageplaza\SocialLogin\Hybrid\Auth', ['config' => $config]);

			return $auth;
		} catch (\Exception $e) {
			echo "Ooophs, we got an error: " . $e->getMessage();
			die;
		}
	}

	public function getProviderData()
	{
		$data = [
			"enabled" => $this->apiHelper->isEnabled(),
			"keys"    => [
				'id'     => $this->apiHelper->getAppId(),
				'secret' => $this->apiHelper->getAppSecret()
			]
		];

		return array_merge($data, $this->getProviderAdditionData());
	}

	public function getProviderAdditionData()
	{
		return [];
	}

	public function getEndpoint()
	{
		return $this->objectManager->create('Mageplaza\SocialLogin\Hybrid\Endpoint');
	}

}