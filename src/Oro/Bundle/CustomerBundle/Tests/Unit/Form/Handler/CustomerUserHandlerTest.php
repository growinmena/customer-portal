<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserHandler;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormHandlerTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomerUserHandlerTest extends FormHandlerTestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CustomerBundle\Entity\CustomerUserManager
     */
    protected $userManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $passwordGenerateForm;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $sendEmailForm;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var CustomerUser
     */
    protected $entity;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new CustomerUser();

        $this->userManager = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\CustomerUserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->passwordGenerateForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sendEmailForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->logger = $this->createMock('Psr\Log\LoggerInterface');

        $this->handler = new CustomerUserHandler(
            $this->form,
            $this->request,
            $this->userManager,
            $this->securityFacade,
            $this->translator,
            $this->logger
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $organization = null;
        if ($isValid) {
            $organization = new Organization();
            $organization->setName('test');

            $organizationToken =
                $this->createMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
            $organizationToken->expects($this->any())
                ->method('getOrganizationContext')
                ->willReturn($organization);

            $this->securityFacade->expects($this->any())
                ->method('getToken')
                ->willReturn($organizationToken);

            $this->form->expects($this->at(2))
                ->method('get')
                ->with('passwordGenerate')
                ->will($this->returnValue($this->passwordGenerateForm));

            $this->form->expects($this->at(3))
                ->method('get')
                ->with('sendEmail')
                ->will($this->returnValue($this->sendEmailForm));

            $this->passwordGenerateForm->expects($this->once())
                ->method('getData')
                ->will($this->returnValue(false));

            $this->sendEmailForm->expects($this->once())
                ->method('getData')
                ->will($this->returnValue(false));
            $this->userManager->expects($this->once())
                ->method('updateUser')
                ->with($this->entity);
        } else {
            $this->userManager->expects($this->never())
                ->method('updateUser')
                ->with($this->entity);
        }

        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
        if ($organization) {
            $this->assertEquals($organization, $this->entity->getOrganization());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessValidData()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->at(2))
            ->method('get')
            ->with('passwordGenerate')
            ->will($this->returnValue($this->passwordGenerateForm));

        $this->form->expects($this->at(3))
            ->method('get')
            ->with('sendEmail')
            ->will($this->returnValue($this->sendEmailForm));

        $this->passwordGenerateForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(true));

        $this->sendEmailForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(true));

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->assertTrue($this->handler->process($this->entity));
    }

    public function testProcessCurrentUser()
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $organization = new Organization();
        $organization->setName('test');

        $this->assertExistingUserSaveCalls($organization, $customerUser);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUserId')
            ->willReturn(1);
        $this->userManager->expects($this->once())
            ->method('reloadUser')
            ->with($customerUser);

        $this->assertEquals(true, $this->handler->process($customerUser));
        if ($organization) {
            $this->assertEquals($organization, $customerUser->getOrganization());
        }
    }

    public function testProcessAnotherUser()
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 2]);

        $organization = new Organization();
        $organization->setName('test');

        $this->assertExistingUserSaveCalls($organization, $customerUser);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUserId')
            ->willReturn(1);
        $this->userManager->expects($this->never())
            ->method('reloadUser')
            ->with($customerUser);

        $this->assertEquals(true, $this->handler->process($customerUser));
        if ($organization) {
            $this->assertEquals($organization, $customerUser->getOrganization());
        }
    }

    /**
     * @param Organization $organization
     * @param CustomerUser $customerUser
     */
    protected function assertExistingUserSaveCalls(Organization $organization, CustomerUser $customerUser)
    {
        $organizationToken = $this->createMock(OrganizationContextTokenInterface::class);
        $organizationToken->expects($this->any())
            ->method('getOrganizationContext')
            ->willReturn($organization);

        $this->securityFacade->expects($this->any())
            ->method('getToken')
            ->willReturn($organizationToken);
        $this->userManager->expects($this->never())
            ->method('sendWelcomeEmail');
        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($customerUser);
        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(true));
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
    }
}
