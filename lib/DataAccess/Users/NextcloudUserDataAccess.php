<?php

namespace OCA\SCIMServiceProvider\DataAccess\Users;

use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class NextcloudUserDataAccess
{
    /** @var Psr\Log\LoggerInterface */
    private $logger;

    /** @var \OCP\IUserManager */
    private $userManager;

    /** @var \OCP\Security\ISecureRandom */
    private $secureRandom;

    /** @var IConfig */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
        $this->secureRandom = $container->get(ISecureRandom::class);
		$this->userManager = $container->get(IUserManager::class);
        $this->config = $container->get(IConfig::class);
    }

    /**
     * Read all users
     */
    public function getAll(): ?array
    {
        $ncUsers = $this->userManager->search('', null, 0);

        $this->logger->info(
            "[" . NextcloudUserDataAccess::class . "] fetched " . count($ncUsers) . " users"
        );

        return $ncUsers;
    }

    /**
     * Read a single user by ID
     */
    public function getOneById($id): ?IUser
    {
        $ncUser = $this->userManager->get($id);

        if (!isset($ncUser)) {
            $this->logger->error(
                "[" . NextcloudUserDataAccess::class . "] user with ID: " . $id . " is null"
            );
        } else {
            $this->logger->info(
                "[" . NextcloudUserDataAccess::class . "] fetched user with ID: " . $id
            );
        }

        return $ncUser;
    }

    /**
     * Create a new user
     */
    public function create($username): ?IUser
    {
        $createdNcUser = $this->userManager->createUser($username, $this->secureRandom->generate(64));

        if ($createdNcUser === false) {
            $this->logger->error(
                "[" . NextcloudUserDataAccess::class . "] creation of user with userName: " . $username . " failed"
            );
            return null;
        }

        return $createdNcUser;
    }

    /**
     * Update an existing user by ID
     * 
     * Note: here, we pass the second parameter, since it carries the data to be updated
     * and we need to pass this data to the user that is to be updated
     */
    public function update(string $id, IUser $newUserData): ?IUser
    {
        $ncUserToUpdate = $this->userManager->get($id);

        if ($ncUserToUpdate === null) {
            $this->logger->error(
                "[" . NextcloudUserDataAccess::class . "] user to be updated with ID: " . $id . " doesn't exist"
            );

            return null;
        }

        if ($newUserData->getDisplayName() !== null) {
            $ncUserToUpdate->setDisplayName($newUserData->getDisplayName());
        }

        if ($newUserData->isEnabled() !== null && $newUserData->isEnabled()) {
            $ncUserToUpdate->setEnabled($newUserData->isEnabled());
        }

        if ($newUserData->getEMailAddress() !== null && !empty($newUserData->getEMailAddress())) {
            $ncUserToUpdate->setEMailAddress($newUserData->getEMailAddress());
        }

        // Return the now updated NC user
        return $this->userManager->get($id);
    }

    /**
     * Delete an existing user by ID
     */
    public function delete($id): bool
    {
        $ncUserToDelete = $this->userManager->get($id);

        if ($ncUserToDelete === null) {
            $this->logger->error(
                "[" . NextcloudUserDataAccess::class . "] user to be deleted with ID: " . $id . " doesn't exist"
            );

            return false;
        }

        if ($ncUserToDelete->delete()) {
            return true;
        }

        $this->logger->error(
            "[" . NextcloudUserDataAccess::class . "] couldn't delete user with ID: " . $id
        );

        return false;
    }
}
