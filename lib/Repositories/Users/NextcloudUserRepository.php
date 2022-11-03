<?php

namespace OCA\SCIMServiceProvider\Repositories\Users;

use Opf\Models\SCIM\Standard\Users\CoreUser;
use Opf\Repositories\Repository;
use Opf\Util\Filters\FilterUtil;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class NextcloudUserRepository extends Repository
{
    /** @var Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->dataAccess = $container->get('UserDataAccess');
        $this->adapter = $container->get('UserAdapter');
        $this->logger = $container->get(LoggerInterface::class);
    }

    /**
     * Read all users in SCIM format
     */
    public function getAll(
        $filter = '',
        $startIndex = 0,
        $count = 0,
        $attributes = [],
        $excludedAttributes = []
    ): array {
        $this->logger->info(
            "[" . NextcloudUserRepository::class . "] reading all users"
        );

        // Read all NC users
        $ncUsers = $this->dataAccess->getAll();
        $scimUsers = [];

        $this->logger->info(
            "[" . NextcloudUserRepository::class . "] fetched " . count($ncUsers) . " NC users"
        );

        foreach ($ncUsers as $ncUser) {
            $scimUser = $this->adapter->getCoreUser($ncUser);
            $scimUsers[] = $scimUser;
        }

        $this->logger->info(
            "[" . NextcloudUserRepository::class . "] transformed " . count($scimUsers) . " SCIM users"
        );

        if (isset($filter) && !empty($filter)) {
            $scimUsersToFilter = [];
            foreach ($scimUsers as $scimUser) {
                $scimUsersToFilter[] = $scimUser->toSCIM(false);
            }

            $filteredScimData = FilterUtil::performFiltering($filter, $scimUsersToFilter);

            $scimUsers = [];
            foreach ($filteredScimData as $filteredScimUser) {
                $scimUser = new CoreUser();
                $scimUser->fromSCIM($filteredScimUser);
                $scimUsers[] = $scimUser;
            }

            return $scimUsers;
        }

        return $scimUsers;
    }

    /**
     * Read a single user by ID in SCIM format
     */
    public function getOneById(
        string $id,
        $filter = '',
        $startIndex = 0,
        $count = 0,
        $attributes = [],
        $excludedAttributes = []
    ): ?CoreUser {
        $this->logger->info(
            "[" . NextcloudUserRepository::class . "] reading user with ID: " . $id
        );

        $ncUser = $this->dataAccess->getOneById($id);
        $scimUser = $this->adapter->getCoreUser($ncUser);

        if (isset($filter) && !empty($filter)) {
            $scimUsersToFilter = array($scimUser->toSCIM(false));
            $filteredScimData = FilterUtil::performFiltering($filter, $scimUsersToFilter);

            if (!empty($filteredScimData)) {
                $scimUser = new CoreUser();
                $scimUser->fromSCIM($filteredScimData[0]);
                return $scimUser;
            }
        }

        return $scimUser;
    }

    /**
     * Create a user from SCIM data
     */
    public function create($object): ?CoreUser
    {
        $scimUserToCreate = new CoreUser();
        $scimUserToCreate->fromSCIM($object);

        $username = $scimUserToCreate->getUserName();
        $ncUserCreated = $this->dataAccess->create($username);

        $this->logger->info(
            "[" . NextcloudUserRepository::class . "] creating user with userName: " . $username
        );

        if (isset($ncUserCreated)) {
            // Set the rest of the properties of the NC user via the adapter
            $ncUserCreated = $this->adapter->getNCUser($scimUserToCreate, $ncUserCreated);
            return $this->adapter->getCoreUser($ncUserCreated);
        }

        $this->logger->error(
            "[" . NextcloudUserRepository::class . "] creation of user with username: " . $username . " failed"
        );

        return null;
    }

    /**
     * Update a user by ID from SCIM data
     */
    public function update(string $id, $object): ?CoreUser
    {
        $this->logger->info(
            "[" . NextcloudUserRepository::class . "] updating user with ID: " . $id
        );

        $scimUserToUpdate = new CoreUser();
        $scimUserToUpdate->fromSCIM($object);

        $ncUser = $this->dataAccess->getOneById($id);

        if (isset($ncUser)) {
            $ncUserToUpdate = $this->adapter->getNCUser($scimUserToUpdate, $ncUser);
            $ncUserUpdated = $this->dataAccess->update($id, $ncUserToUpdate);
            
            if (isset($ncUserUpdated)) {
                return $this->adapter->getCoreUser($ncUserUpdated);
            }
        }

        $this->logger->error(
            "[" . NextcloudUserRepository::class . "] update of user with ID: " . $id . " failed"
        );

        return null;
    }

    /**
     * Delete a user by ID
     */
    public function delete(string $id): bool
    {
        $this->logger->info(
            "[" . NextcloudUserRepository::class . "] deleting user with ID: " . $id
        );

        return $this->dataAccess->delete($id);
    }
}
