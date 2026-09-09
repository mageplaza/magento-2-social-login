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

declare(strict_types=1);

namespace Mageplaza\SocialLogin\Test\Unit\Model;

use Hybridauth\Storage\Session as HybridAuthSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Social::class)]
class SocialTest extends TestCase
{
    /**
     * @var CustomerFactory|MockObject
     */
    private $customerFactory;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    private $customerDataFactory;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var SocialHelper|MockObject
     */
    private $apiHelper;

    /**
     * @var HybridAuthSession|MockObject
     */
    private $hybridAuthSession;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Social
     */
    private $model;

    protected function setUp(): void
    {
        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $this->customerDataFactory = $this->getMockBuilder(CustomerInterfaceFactory::class)
            ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->apiHelper = $this->getMockBuilder(SocialHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getType', 'setType', 'isEnabled', 'getAppId', 'getAppSecret',
                'getAppPublicKey', 'getSocialConfig', 'canSendPassword',
            ])
            ->getMock();
        $this->hybridAuthSession = $this->getMockBuilder(HybridAuthSession::class)
            ->disableOriginalConstructor()->onlyMethods(['get'])->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)->getMock();

        $this->model = (new ObjectManager($this))->getObject(Social::class, [
            'customerFactory'     => $this->customerFactory,
            'customerDataFactory' => $this->customerDataFactory,
            'customerRepository'  => $this->customerRepository,
            'storeManager'        => $this->storeManager,
            'apiHelper'           => $this->apiHelper,
            'hybridAuthSession'   => $this->hybridAuthSession,
            'request'             => $this->request,
        ]);
    }

    // ----------------------------------------------------------------- getProviderData

    public function testGetProviderDataBuildsFacebookKeysAndAdapter(): void
    {
        $this->apiHelper->method('getType')->willReturn(null);
        $this->apiHelper->expects($this->once())->method('setType')->with('facebook');
        $this->apiHelper->method('isEnabled')->willReturn(true);
        $this->apiHelper->method('getAppId')->willReturn('app-id');
        $this->apiHelper->method('getAppSecret')->willReturn('app-secret');
        $this->apiHelper->method('getAppPublicKey')->willReturn('pub-key');
        $this->apiHelper->method('getSocialConfig')->with('facebook')
            ->willReturn(['scope' => 'email, public_profile']);

        $data = $this->model->getProviderData('facebook');

        $this->assertTrue($data['enabled']);
        $this->assertSame('app-id', $data['keys']['id']);
        $this->assertSame('app-secret', $data['keys']['secret']);
        $this->assertSame('', $data['keys']['public_key']); // only odnoklassniki sets it
        $this->assertSame('Mageplaza\SocialLogin\Model\Providers\Facebook', $data['adapter']);
        $this->assertSame('email, public_profile', $data['scope']); // merged provider config
    }

    public function testGetProviderDataSteamHasEmptySecretAndNoAdapter(): void
    {
        $this->apiHelper->method('getType')->willReturn('steam'); // already set -> setType skipped
        $this->apiHelper->expects($this->never())->method('setType');
        $this->apiHelper->method('isEnabled')->willReturn(false);
        $this->apiHelper->method('getAppId')->willReturn('id');
        $this->apiHelper->method('getSocialConfig')->willReturn([]);

        $data = $this->model->getProviderData('steam');

        $this->assertSame('', $data['keys']['secret']);
        $this->assertNull($data['adapter']);
        $this->assertFalse($data['enabled']);
    }

    public function testGetProviderDataOdnoklassnikiSetsPublicKeyAndProAdapter(): void
    {
        $this->apiHelper->method('getType')->willReturn('odnoklassniki');
        $this->apiHelper->method('isEnabled')->willReturn(true);
        $this->apiHelper->method('getAppId')->willReturn('id');
        $this->apiHelper->method('getAppSecret')->willReturn('secret');
        $this->apiHelper->method('getAppPublicKey')->willReturn('public-123');
        $this->apiHelper->method('getSocialConfig')->willReturn([]);

        $data = $this->model->getProviderData('odnoklassniki');

        $this->assertSame('public-123', $data['keys']['public_key']);
        $this->assertSame('secret', $data['keys']['secret']);
        $this->assertSame('Mageplaza\SocialLoginPro\Model\Providers\Odnoklassniki', $data['adapter']);
    }

    // ----------------------------------------------------------------- getProviderConnected

    public function testGetProviderConnectedReturnsMatchingProvider(): void
    {
        $this->hybridAuthSession->method('get')->willReturnCallback(static function ($key) {
            return $key === 'twitter.request_token' ? 'TOKEN-1' : null;
        });
        $this->request->method('getParam')->willReturnCallback(static function ($key) {
            return $key === 'oauth_token' ? 'TOKEN-1' : null;
        });

        $this->assertSame('twitter', $this->model->getProviderConnected());
    }

    public function testGetProviderConnectedThrowsWhenNoProviderMatches(): void
    {
        $this->hybridAuthSession->method('get')->willReturn(null);
        // Non-empty remote token that never equals any (null) stored state.
        $this->request->method('getParam')->willReturnCallback(static function ($key) {
            return $key === 'oauth_token' ? 'UNMATCHED' : null;
        });

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Unknown Provider');

        $this->model->getProviderConnected();
    }

    // ----------------------------------------------------------------- getCustomerByEmail

    public function testGetCustomerByEmailUsesProvidedWebsiteId(): void
    {
        $customer = $this->getMockBuilder(CustomerEmailDouble::class)
            ->disableOriginalConstructor()->onlyMethods(['setWebsiteId', 'loadByEmail'])->getMock();
        $customer->expects($this->once())->method('setWebsiteId')->with(5);
        $customer->expects($this->once())->method('loadByEmail')->with('jane@example.com');
        $this->customerFactory->method('create')->willReturn($customer);
        $this->storeManager->expects($this->never())->method('getWebsite');

        $this->assertSame($customer, $this->model->getCustomerByEmail('jane@example.com', 5));
    }

    public function testGetCustomerByEmailFallsBackToCurrentWebsite(): void
    {
        $customer = $this->getMockBuilder(CustomerEmailDouble::class)
            ->disableOriginalConstructor()->onlyMethods(['setWebsiteId', 'loadByEmail'])->getMock();
        $customer->expects($this->once())->method('setWebsiteId')->with(1);
        $this->customerFactory->method('create')->willReturn($customer);

        $website = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()->onlyMethods(['getId'])->getMock();
        $website->method('getId')->willReturn(1);
        $this->storeManager->method('getWebsite')->willReturn($website);

        $this->assertSame($customer, $this->model->getCustomerByEmail('jane@example.com'));
    }

    // ----------------------------------------------------------------- createCustomerSocial

    public function testCreateCustomerSocialMapsAlreadyExistsToInputMismatch(): void
    {
        $customerData = $this->createMock(CustomerInterface::class);
        foreach (['setFirstname', 'setLastname', 'setEmail', 'setStoreId', 'setWebsiteId', 'setCreatedIn'] as $setter) {
            $customerData->method($setter)->willReturnSelf();
        }
        $this->customerDataFactory->method('create')->willReturn($customerData);

        $store = $this->getMockBuilder(StoreInterface::class)->getMock();
        $store->method('getId')->willReturn(1);
        $store->method('getWebsiteId')->willReturn(1);
        $store->method('getName')->willReturn('Default Store');

        $this->apiHelper->method('canSendPassword')->willReturn(true);
        $this->customerRepository->method('save')
            ->willThrowException(new AlreadyExistsException(__('exists')));

        $this->expectException(InputMismatchException::class);
        $this->expectExceptionMessage('A customer with the same email already exists in an associated website.');

        $this->model->createCustomerSocial([
            'firstname'  => 'Jane',
            'lastname'   => 'Doe',
            'email'      => 'jane@example.com',
            'password'   => 'secret-pw',
            'identifier' => 'social-id',
            'type'       => 'facebook',
        ], $store);
    }
}
