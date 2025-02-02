<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowMaskBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractLoadACLData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    // existing roles
    const ROLE_FRONTEND_BUYER = 'ROLE_FRONTEND_BUYER';
    const ROLE_FRONTEND_ADMINISTRATOR = 'ROLE_FRONTEND_ADMINISTRATOR';

    const ROLE_BASIC = 'ROLE_BASIC';
    const ROLE_LOCAL = 'ROLE_LOCAL';
    const ROLE_LOCAL_VIEW_ONLY = 'ROLE_LOCAL_VIEW_ONLY';
    const ROLE_DEEP_VIEW_ONLY = 'ROLE_DEEP_VIEW_ONLY';
    const ROLE_DEEP = 'ROLE_DEEP';

    // customer.level_1.1
    const USER_ACCOUNT_1_ROLE_LOCAL = 'customer1-role-local@example.com';
    const USER_ACCOUNT_1_ROLE_BASIC = 'customer1-role-basic@example.com';
    const USER_ACCOUNT_1_ROLE_DEEP = 'customer1-role-deep@example.com';
    const USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY = 'customer1-role-local-view-only@example.com';
    const USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY = 'customer1-role-deep-view-only@example.com';

    // customer.level_1.1.1
    const USER_ACCOUNT_1_1_ROLE_LOCAL = 'customer1-1-role-local@example.com';
    const USER_ACCOUNT_1_1_ROLE_BASIC = 'customer1-1-role-basic@example.com';
    const USER_ACCOUNT_1_1_ROLE_DEEP = 'customer1-1-role-deep@example.com';

    // customer.level_1.1.2
    const USER_ACCOUNT_1_2_ROLE_LOCAL = 'customer1-2-role-local@example.com';
    const USER_ACCOUNT_1_2_ROLE_BASIC = 'customer1-2-role-basic@example.com';
    const USER_ACCOUNT_1_2_ROLE_DEEP = 'customer1-2-role-deep@example.com';

    // customer.level_1.2
    const USER_ACCOUNT_2_ROLE_LOCAL = 'customer2-role-local@example.com';
    const USER_ACCOUNT_2_ROLE_BASIC = 'customer2-role-basic@example.com';
    const USER_ACCOUNT_2_ROLE_DEEP = 'customer2-role-deep@example.com';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var User
     */
    protected $admin;

    /**
     * @return array
     */
    public static function getCustomerUsers()
    {
        return [
            [
                'email' => static::USER_ACCOUNT_1_ROLE_BASIC,
                'customer' => 'customer.level_1.1',
                'role' => static::ROLE_BASIC,
            ],
            [
                'email' => static::USER_ACCOUNT_1_ROLE_LOCAL,
                'customer' => 'customer.level_1.1',
                'role' => static::ROLE_LOCAL,
            ],
            [
                'email' => static::USER_ACCOUNT_1_ROLE_DEEP,
                'customer' => 'customer.level_1.1',
                'role' => static::ROLE_DEEP,
            ],
            [
                'email' => static::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'customer' => 'customer.level_1.1',
                'role' => static::ROLE_LOCAL_VIEW_ONLY,
            ],
            [
                'email' => static::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'customer' => 'customer.level_1.1',
                'role' => static::ROLE_DEEP_VIEW_ONLY,
            ],
            [
                'email' => static::USER_ACCOUNT_1_1_ROLE_BASIC,
                'customer' => 'customer.level_1.1.1',
                'role' => static::ROLE_BASIC,
            ],
            [
                'email' => static::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'customer' => 'customer.level_1.1.1',
                'role' => static::ROLE_LOCAL,
            ],
            [
                'email' => static::USER_ACCOUNT_1_1_ROLE_DEEP,
                'customer' => 'customer.level_1.1.1',
                'role' => static::ROLE_DEEP,
            ],
            [
                'email' => static::USER_ACCOUNT_1_2_ROLE_BASIC,
                'customer' => 'customer.level_1.1.2',
                'role' => static::ROLE_BASIC,
            ],
            [
                'email' => static::USER_ACCOUNT_1_2_ROLE_LOCAL,
                'customer' => 'customer.level_1.1.2',
                'role' => static::ROLE_LOCAL,
            ],
            [
                'email' => static::USER_ACCOUNT_1_2_ROLE_DEEP,
                'customer' => 'customer.level_1.1.2',
                'role' => static::ROLE_DEEP,
            ],
            [
                'email' => static::USER_ACCOUNT_2_ROLE_BASIC,
                'customer' => 'customer.level_1.2',
                'role' => static::ROLE_BASIC,
            ],
            [
                'email' => static::USER_ACCOUNT_2_ROLE_LOCAL,
                'customer' => 'customer.level_1.2',
                'role' => static::ROLE_LOCAL,
            ],
            [
                'email' => static::USER_ACCOUNT_2_ROLE_DEEP,
                'customer' => 'customer.level_1.2',
                'role' => static::ROLE_DEEP,
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomers::class,
        ];
    }

    /**
     * @return string|array
     */
    abstract protected function getAclResourceClassName();

    /**
     * @return array
     */
    abstract protected function getSupportedRoles();

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadRoles($manager);
        $this->loadCustomerUsers($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadCustomerUsers(ObjectManager $manager)
    {
        /* @var $userManager CustomerUserManager */
        $userManager = $this->container->get('oro_customer_user.manager');

        $defaultUser = $this->getAdminUser($manager);
        $organization = $defaultUser->getOrganization();

        $supportedRoles = $this->getSupportedRoles();
        foreach (static::getCustomerUsers() as $item) {
            if (!in_array($item['role'], $supportedRoles)) {
                continue;
            }
            $customerUser = null;
            if ($this->hasReference($item['email'])) {
                $customerUser = $this->getReference($item['email']);
            } else {
                /* @var $customerUser CustomerUser */
                $customerUser = $userManager->createUser();
                $customerUser
                    ->setEmail($item['email'])
                    ->setCustomer($this->getReference($item['customer']))
                    ->setOwner($defaultUser)
                    ->setFirstName($item['email'])
                    ->setLastName($item['email'])
                    ->setConfirmed(true)
                    ->setOrganization($organization)
                    ->setPlainPassword($item['email']);
                $this->setReference($item['email'], $customerUser);
            }
            /** @var Role $role */
            $role = $this->getReference($item['role']);
            $customerUser
                ->addRole($role)
                ->setEnabled(true)
                ->setSalt('');

            $userManager->updateUser($customerUser);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadRoles(ObjectManager $manager)
    {
        $user = $this->getAdminUser($manager);
        $repository = $manager->getRepository('OroCustomerBundle:CustomerUserRole');
        $this->setReference(self::ROLE_FRONTEND_BUYER, $repository->findOneBy(['role' => 'ROLE_FRONTEND_BUYER']));
        $this->setReference(
            self::ROLE_FRONTEND_ADMINISTRATOR,
            $repository->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR'])
        );

        $roles = [
            static::ROLE_BASIC => [['VIEW_BASIC', 'CREATE_BASIC', 'EDIT_BASIC'], ['DELETE_BASIC']],
            static::ROLE_LOCAL => [['VIEW_LOCAL', 'CREATE_LOCAL', 'EDIT_LOCAL'], ['DELETE_LOCAL', 'ASSIGN_LOCAL']],
            static::ROLE_LOCAL_VIEW_ONLY => [['VIEW_LOCAL'], []],
            static::ROLE_DEEP => [['VIEW_DEEP', 'CREATE_DEEP', 'EDIT_DEEP'], ['DELETE_DEEP', 'ASSIGN_DEEP']],
            static::ROLE_DEEP_VIEW_ONLY => [['VIEW_DEEP'], []],
        ];

        foreach ($roles as $key => $permissions) {
            if (!in_array($key, $this->getSupportedRoles())) {
                continue;
            }
            $role = new CustomerUserRole(CustomerUserRole::PREFIX_ROLE.$key);
            $role->setLabel($key)
                ->setSelfManaged(true)
                ->setOrganization($user->getOrganization());
            $classnames = (array) $this->getAclResourceClassName();
            foreach ($classnames as $class) {
                $this->setRolePermissions($role, $class, $permissions);
                $this->setWorkflowPermissions($role);
            }

            $manager->persist($role);
            $this->setReference($key, $role);
        }

        $manager->flush();
        $this->container->get('oro_security.acl.manager')->flush();
    }

    /**
     * @param CustomerUserRole $role
     */
    protected function setWorkflowPermissions(CustomerUserRole $role)
    {
        $aclManager = $this->container->get('oro_security.acl.manager');
        $sid = $aclManager->getSid($role);
        $oid = $aclManager->getOid('workflow:(root)');

        $aclManager->setPermission($sid, $oid, WorkflowMaskBuilder::GROUP_SYSTEM);
    }

    /**
     * @param CustomerUserRole $role
     * @param string $className
     * @param array $allowedACL
     */
    protected function setRolePermissions(CustomerUserRole $role, $className, array $allowedACL)
    {
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $aclManager = $this->container->get('oro_security.acl.manager');
        $sid = $aclManager->getSid($role);
        $oid = $aclManager->getOid('entity:'.$className);

        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        foreach ($aclManager->getAllExtensions() as $extension) {
            if ($extension instanceof EntityAclExtension) {
                $builder = $aclManager->getMaskBuilder($oid);
                $mask = $builder->reset()->get();
                foreach ($allowedACL[0] as $acl) {
                    $mask = $builder->add($acl)->get();
                }

                $deleteBuilder = $aclManager->getMaskBuilder($oid, 'DELETE');
                $deleteMask = $deleteBuilder->reset()->get();
                foreach ($allowedACL[1] as $acl) {
                    $deleteMask = $deleteBuilder->add($acl)->get();
                }

                $aclManager->setPermission($sid, $oid, $mask);
                $aclManager->setPermission($sid, $oid, $deleteMask);
            } elseif ($extension instanceof ActionAclExtension) {
                $capabilityOID = $aclManager->getRootOid($extension->getExtensionKey());
                $builder = $aclManager->getMaskBuilder($capabilityOID);

                $aclManager->setPermission($sid, $capabilityOID, $builder->getMask('GROUP_ALL'));
            }
        }

        $chainMetadataProvider->stopProviderEmulation();
    }

    /**
     * @param ObjectManager $manager
     * @return User
     */
    protected function getAdminUser(ObjectManager $manager)
    {
        if (null === $this->admin) {
            $repo = $manager->getRepository(Role::class);

            $role = $repo->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);
            $this->admin = $repo->getFirstMatchedUser($role);
        }

        return $this->admin;
    }
}
