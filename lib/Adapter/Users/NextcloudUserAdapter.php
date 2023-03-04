<?php

namespace OCA\SCIMServiceProvider\Adapter\Users;

use OCA\SCIMServiceProvider\AppInfo\Application;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Opf\Adapters\AbstractAdapter;
use Opf\Models\SCIM\Standard\MultiValuedAttribute;
use Opf\Models\SCIM\Standard\Users\CoreUser;
use Opf\Models\SCIM\Standard\Users\Name;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class NextcloudUserAdapter extends AbstractAdapter
{
    /** @var Psr\Log\LoggerInterface */
    private $logger;

    /** @var IConfig */
    private $config;

    /** @var IUserManager */
    private $userManager;

    /** @var ISecureRandom */
    private $secureRandom;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
        $this->config = $container->get(IConfig::class);
        $this->userManager = $container->get(IUserManager::class);
        $this->secureRandom = $container->get(ISecureRandom::class);
    }

    /**
     * Transform an NC User into a SCIM user
     */
    public function getCoreUser(?IUser $ncUser): ?CoreUser
    {
        $this->logger->info(
            "[" . NextcloudUserAdapter::class . "] entering getCoreUser() method"
        );

        if (!isset($ncUser)) {
            $this->logger->error(
                "[" . NextcloudUserAdapter::class . "] passed NC user in getCoreUser() method is null"
            );

            return null;
        }

        $coreUser = new CoreUser();
        $coreUser->setId($ncUser->getUID());
        
        $coreUserName = new Name();
        $coreUserName->setFormatted($ncUser->getDisplayName());
        $coreUser->setName($coreUserName);

        $coreUser->setUserName($ncUser->getUID());
        $coreUser->setDisplayName($ncUser->getDisplayName());
        $coreUser->setActive($ncUser->isEnabled());

		$ncUserExternalId = $this->config->getUserValue($ncUser->getUID(), Application::APP_ID, 'externalId', '');
        $coreUser->setExternalId($ncUserExternalId);

        if ($ncUser->getEMailAddress() !== null && !empty($ncUser->getEMailAddress())) {
            $coreUserEmail = new MultiValuedAttribute();
            $coreUserEmail->setValue($ncUser->getEMailAddress());
            $coreUserEmail->setPrimary(true);

            $coreUser->setEmails(array($coreUserEmail));
        }

        return $coreUser;
    }

    /**
     * Transform a SCIM user into an NC User
     * 
     * Note: we need the second parameter, since we can't instantiate an NC user in PHP
     * ourselves and need to receive an instance that we can populate with data from the SCIM user
     */
    public function getNCUser(?CoreUser $coreUser, IUser $ncUser): ?IUser
    {
        $this->logger->info(
            "[" . NextcloudUserAdapter::class . "] entering getNCUser() method"
        );

        if (!isset($coreUser) || !isset($ncUser)) {
            $this->logger->error(
                "[" . NextcloudUserAdapter::class . "] passed Core User in getNCUser() method is null"
            );

            return null;
        }

        if ($coreUser->getDisplayName() !== null && !empty($coreUser->getDisplayName())) {
            $ncUser->setDisplayName($coreUser->getDisplayName());
        }

        if ($coreUser->getActive() !== null) {
            $ncUser->setEnabled($coreUser->getActive());
        }

        if ($coreUser->getExternalId() !== null && !empty($coreUser->getExternalId())) {
            $this->config->setUserValue($ncUser->getUID(), Application::APP_ID, 'externalId', $coreUser->getExternalId());
        }

        if ($coreUser->getEmails() !== null && !empty($coreUser->getEmails())) {
            // Here, we use the first email of the SCIM user to set as the NC user's email
            // TODO: is this ok or should we rather first iterate and search for a primary email of the SCIM user
            if ($coreUser->getEmails()[0] !== null && !empty($coreUser->getEmails()[0])) {
                if ($coreUser->getEmails()[0]->getValue() !== null && !empty($coreUser->getEmails()[0]->getValue())) {
                    $ncUser->setEMailAddress($coreUser->getEmails()[0]->getValue());
                }   
            }
        }

        return $ncUser;
    }
}
