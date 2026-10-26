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

namespace Mageplaza\SocialLogin\Test\Unit\Controller\Social;

use Hybridauth\User\Profile;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecure;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\Store;
use Mageplaza\SocialLogin\Controller\Social\AbstractSocial;
use Mageplaza\SocialLogin\Controller\Social\Email;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;
use Mageplaza\SocialLogin\Test\Unit\Model\CustomerEmailDouble;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Declared-method double: Session::getUserProfile() is a magic getter
 */
abstract class CustomerSessionDouble extends Session
{
    abstract public function getUserProfile();
}

#[CoversClass(Email::class)]
class EmailTest extends TestCase
{
    private const CUSTOMER_ID = 7;

    /**
     * @var Email|MockObject
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CustomerSessionDouble|MockObject
     */
    private $session;

    /**
     * @var Social|MockObject
     */
    private $apiObject;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerSecure
     */
    private $customerSecure;

    /**
     * @var array
     */
    private $resultData = [];

    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore', '_loginPostRedirect', 'createCustomerProcess', 'refresh'])
            ->getMock();

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()->onlyMethods(['getWebsiteId'])->getMock();
        $store->method('getWebsiteId')->willReturn(1);
        $this->controller->method('getStore')->willReturn($store);
        $this->controller->method('_loginPostRedirect')->willReturn('http://example.test/customer/account');

        $this->request = $this->createMock(RequestInterface::class);

        $apiHelper = $this->getMockBuilder(SocialHelper::class)
            ->disableOriginalConstructor()->onlyMethods(['setType'])->getMock();
        $apiHelper->method('setType')->willReturn('Google');

        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('getHash')->willReturnCallback(static fn($password) => 'hash:' . $password);

        $this->session = $this->getMockBuilder(CustomerSessionDouble::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserProfile', 'setCustomerDataAsLoggedIn', 'regenerateId'])
            ->getMock();

        $this->apiObject = $this->getMockBuilder(Social::class)
            ->disableOriginalConstructor()->onlyMethods(['getCustomerBySocial'])->getMock();

        $existing = $this->getMockBuilder(CustomerEmailDouble::class)
            ->disableOriginalConstructor()->onlyMethods(['setWebsiteId', 'loadByEmail', 'getId'])->getMock();
        $existing->method('setWebsiteId')->willReturnSelf();
        $existing->method('loadByEmail')->willReturnSelf();
        $existing->method('getId')->willReturn(self::CUSTOMER_ID);
        $customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $customerFactory->method('create')->willReturn($existing);

        $this->customerSecure = new CustomerSecure();
        $registry = $this->getMockBuilder(CustomerRegistry::class)
            ->disableOriginalConstructor()->onlyMethods(['retrieveSecureData'])->getMock();
        $registry->method('retrieveSecureData')->with(self::CUSTOMER_ID)->willReturn($this->customerSecure);

        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerRepository->method('getById')->willReturn($this->createMock(CustomerInterface::class));

        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()->onlyMethods(['setData'])->getMock();
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->resultData = $data;

            return $json;
        });
        $jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $jsonFactory->method('create')->willReturn($json);

        $this->setProperty(AbstractAction::class, '_request', $this->request);
        $this->setProperty(AbstractSocial::class, 'apiHelper', $apiHelper);
        $this->setProperty(AbstractSocial::class, 'apiObject', $this->apiObject);
        $this->setProperty(AbstractSocial::class, 'session', $this->session);
        $this->setProperty(Email::class, '_encrypt', $encryptor);
        $this->setProperty(Email::class, 'customerFactory', $customerFactory);
        $this->setProperty(Email::class, '_customerRegistry', $registry);
        $this->setProperty(Email::class, '_customerRepositoryInterface', $this->customerRepository);
        $this->setProperty(Email::class, 'resultJsonFactory', $jsonFactory);
    }

    private function setProperty(string $class, string $name, $value): void
    {
        (new ReflectionProperty($class, $name))->setValue($this->controller, $value);
    }

    private function givenProfile(): void
    {
        $profile             = new Profile();
        $profile->identifier = 'social-123';
        $profile->email      = 'jane@example.com';
        $this->session->method('getUserProfile')->willReturn($profile);
        $this->request->method('getParams')->willReturn(['type' => 'google', 'password' => 'NewPass123']);
    }

    /**
     * @param int|null $id
     */
    private function givenLinkedCustomer($id): void
    {
        $linked = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()->onlyMethods(['getId'])->getMock();
        $linked->method('getId')->willReturn($id);
        $this->apiObject->method('getCustomerBySocial')->with('social-123', 'Google')->willReturn($linked);
    }

    public function testPasswordIsNotChangedWhenAccountIsNotLinkedToSocialIdentity(): void
    {
        $this->givenProfile();
        $this->givenLinkedCustomer(null);
        $this->customerSecure->setPasswordHash('original-hash');
        $this->customerRepository->expects($this->never())->method('save');
        $this->session->expects($this->never())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertFalse($this->resultData['success']);
        $this->assertSame('original-hash', $this->customerSecure->getPasswordHash());
    }

    public function testPasswordIsNotChangedWhenLinkedToAnotherAccount(): void
    {
        $this->givenProfile();
        $this->givenLinkedCustomer(99);
        $this->customerRepository->expects($this->never())->method('save');
        $this->session->expects($this->never())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertFalse($this->resultData['success']);
        $this->assertNull($this->customerSecure->getPasswordHash());
    }

    public function testPasswordIsNotChangedWhenLinkedAccountAlreadyHasPassword(): void
    {
        $this->givenProfile();
        $this->givenLinkedCustomer(self::CUSTOMER_ID);
        $this->customerSecure->setPasswordHash('original-hash');
        $this->customerRepository->expects($this->never())->method('save');
        $this->session->expects($this->never())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertFalse($this->resultData['success']);
        $this->assertSame('original-hash', $this->customerSecure->getPasswordHash());
    }

    public function testLinkedPasswordlessAccountCanSetPassword(): void
    {
        $this->givenProfile();
        $this->givenLinkedCustomer(self::CUSTOMER_ID);
        $this->customerRepository->expects($this->once())->method('save');
        $this->session->expects($this->once())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertTrue($this->resultData['success']);
        $this->assertSame('hash:NewPass123', $this->customerSecure->getPasswordHash());
    }

    public function testMissingSocialProfileInSessionIsRejected(): void
    {
        $this->session->method('getUserProfile')->willReturn(null);
        $this->request->method('getParams')->willReturn(['type' => 'google', 'password' => 'NewPass123']);
        $this->customerRepository->expects($this->never())->method('save');

        $this->controller->execute();

        $this->assertFalse($this->resultData['success']);
    }
}
