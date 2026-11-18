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
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
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

        $store = $this->createStub(Store::class);
        $store->method('getWebsiteId')->willReturn(1);
        $this->controller->method('getStore')->willReturn($store);
        $this->controller->method('_loginPostRedirect')->willReturn('http://example.test/customer/account');
        $this->controller->expects($this->never())->method('refresh');

        $this->request = $this->createStub(RequestInterface::class);

        $apiHelper = $this->createStub(SocialHelper::class);
        $apiHelper->method('setType')->willReturn('Google');

        $encryptor = $this->createStub(EncryptorInterface::class);
        $encryptor->method('getHash')->willReturnCallback(static fn($password) => 'hash:' . $password);

        $this->session = $this->getMockBuilder(CustomerSessionDouble::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserProfile', 'isLoggedIn', 'getCustomerId', 'setCustomerDataAsLoggedIn', 'regenerateId'])
            ->getMock();

        $this->apiObject = $this->createStub(Social::class);

        $existing = $this->createStub(CustomerEmailDouble::class);
        $existing->method('setWebsiteId')->willReturnSelf();
        $existing->method('loadByEmail')->willReturnSelf();
        $existing->method('getId')->willReturn(self::CUSTOMER_ID);
        $customerFactory = $this->createStub(CustomerFactory::class);
        $customerFactory->method('create')->willReturn($existing);

        $this->customerSecure = new CustomerSecure();
        $registry = $this->createStub(CustomerRegistry::class);
        $registry->method('retrieveSecureData')->willReturnCallback(function ($customerId) {
            $this->assertEquals(self::CUSTOMER_ID, $customerId);

            return $this->customerSecure;
        });

        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerRepository->method('getById')->willReturn($this->createStub(CustomerInterface::class));

        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->resultData = $data;

            return $json;
        });
        $jsonFactory = $this->createStub(JsonFactory::class);
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
        $linked = $this->createStub(Customer::class);
        $linked->method('getId')->willReturn($id);
        $this->apiObject = $this->createMock(Social::class);
        $this->setProperty(AbstractSocial::class, 'apiObject', $this->apiObject);
        $this->apiObject->expects($this->once())->method('getCustomerBySocial')
            ->with('social-123', 'Google')->willReturn($linked);
    }

    public function testLoggedInCustomerLinksProfileWithoutChangingPassword(): void
    {
        $this->apiObject = $this->createMock(Social::class);
        $this->setProperty(AbstractSocial::class, 'apiObject', $this->apiObject);
        $this->controller->expects($this->once())->method('_loginPostRedirect');
        $this->givenProfile();
        $this->session->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(self::CUSTOMER_ID);
        $this->customerSecure->setPasswordHash('original-hash');
        $this->apiObject->expects($this->once())->method('linkToCustomer')
            ->with('social-123', self::CUSTOMER_ID, 'Google');
        $this->customerRepository->expects($this->never())->method('save');

        $this->controller->execute();

        $this->assertTrue($this->resultData['success']);
        $this->assertSame('original-hash', $this->customerSecure->getPasswordHash());
    }

    public function testPasswordIsNotChangedWhenAccountIsNotLinkedToSocialIdentity(): void
    {
        $this->controller->expects($this->never())->method('_loginPostRedirect');
        $this->givenProfile();
        $this->givenLinkedCustomer(null);
        $this->customerSecure->setPasswordHash('original-hash');
        $this->customerRepository->expects($this->never())->method('save');
        $this->session->expects($this->never())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertFalse($this->resultData['success']);
        $this->assertSame(
            'An account with this email already exists. Please sign in with your password first.',
            (string) $this->resultData['message']
        );
        $this->assertSame('original-hash', $this->customerSecure->getPasswordHash());
    }

    public function testPasswordIsNotChangedWhenLinkedToAnotherAccount(): void
    {
        $this->controller->expects($this->never())->method('_loginPostRedirect');
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
        $this->controller->expects($this->once())->method('_loginPostRedirect');
        $this->givenProfile();
        $this->givenLinkedCustomer(self::CUSTOMER_ID);
        $this->customerSecure->setPasswordHash('original-hash');
        $this->customerRepository->expects($this->never())->method('save');
        $this->session->expects($this->once())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertTrue($this->resultData['success']);
        $this->assertSame('original-hash', $this->customerSecure->getPasswordHash());
    }

    public function testLinkedPasswordlessAccountCanSetPassword(): void
    {
        $this->controller->expects($this->once())->method('_loginPostRedirect');
        $this->givenProfile();
        $this->givenLinkedCustomer(self::CUSTOMER_ID);
        $this->customerRepository->expects($this->once())->method('save');
        $this->session->expects($this->once())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertTrue($this->resultData['success']);
        $this->assertSame('hash:NewPass123', $this->customerSecure->getPasswordHash());
    }

    public function testUserTypedEmailCreatesAccountWithoutLoggingIn(): void
    {
        $this->customerRepository->expects($this->never())->method('save');
        $profile             = new Profile();
        $profile->identifier = 'social-123';
        $profile->email      = null;
        $this->session->method('getUserProfile')->willReturn($profile);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->request->method('getParams')->willReturn(['type' => 'google', 'realEmail' => 'victim@example.com']);

        $unknown = $this->createStub(CustomerEmailDouble::class);
        $unknown->method('setWebsiteId')->willReturnSelf();
        $unknown->method('loadByEmail')->willReturnSelf();
        $unknown->method('getId')->willReturn(null);
        $factory = $this->createStub(CustomerFactory::class);
        $factory->method('create')->willReturn($unknown);
        $this->setProperty(Email::class, 'customerFactory', $factory);

        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->expects($this->once())->method('addSuccessMessage');
        $this->setProperty(Action::class, 'messageManager', $messageManager);
        $url = $this->createMock(UrlInterface::class);
        $url->expects($this->once())->method('getUrl')->with('customer/account/login')->willReturn('http://example.test/customer/account/login');
        $this->setProperty(Action::class, '_url', $url);

        $this->controller->expects($this->once())->method('createCustomerProcess')
            ->willReturn($this->createStub(Customer::class));
        $this->controller->expects($this->never())->method('refresh');
        $this->session->expects($this->never())->method('setCustomerDataAsLoggedIn');

        $this->controller->execute();

        $this->assertTrue($this->resultData['success']);
        $this->assertSame('http://example.test/customer/account/login', $this->resultData['url']);
        $this->assertSame('victim@example.com', $profile->email);
    }

    public function testRealEmailOfExistingCustomerIsRejectedBeforeAnyAccountWork(): void
    {
        $profile             = new Profile();
        $profile->identifier = 'social-123';
        $profile->email      = null;
        $this->session->method('getUserProfile')->willReturn($profile);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->request->method('getParams')->willReturn(['type' => 'google', 'realEmail' => 'victim@example.com']);

        $this->controller->expects($this->never())->method('createCustomerProcess');
        $this->session->expects($this->never())->method('setCustomerDataAsLoggedIn');
        $this->customerRepository->expects($this->never())->method('save');

        $this->controller->execute();

        $this->assertFalse($this->resultData['success']);
        $this->assertSame('Email already exists', (string) $this->resultData['message']);
        $this->assertNull($profile->email);
    }

    public function testMissingSocialProfileInSessionIsRejected(): void
    {
        $this->controller->expects($this->never())->method('_loginPostRedirect');
        $this->session->expects($this->once())->method('getUserProfile')->willReturn(null);
        $this->request->method('getParams')->willReturn(['type' => 'google', 'password' => 'NewPass123']);
        $this->customerRepository->expects($this->never())->method('save');

        $this->controller->execute();

        $this->assertFalse($this->resultData['success']);
    }
}
