<?php

namespace OCA\SCIMServiceProvider\DataAccess\Groups;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class NextcloudGroupDataAccess
{
    /** @var Psr\Log\LoggerInterface */
    private $logger;

    /** @var \OCP\IUserManager */
    private $userManager;

    /** @var \OCP\IGroupManager */
    private $groupManager;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
        $this->userManager = $container->get(IUserManager::class);
        $this->groupManager = $container->get(IGroupManager::class);
    }

    /**
     * Read all groups
     */
    public function getAll(): ?array
    {
        $ncGroups = $this->groupManager->search('', null, 0);

        $this->logger->info(
            "[" . NextcloudGroupDataAccess::class . "] fetched " . count($ncGroups) . " groups"
        );

		return $ncGroups;
    }

    /**
     * Read a single group by ID
     */
    public function getOneById($id): ?IGroup
    {
        $ncGroup = $this->groupManager->get($id);

        if (!isset($ncGroup)) {
            $this->logger->error(
                "[" . NextcloudGroupDataAccess::class . "] group with ID: " . $id . " is null"
            );
        } else {
            $this->logger->info(
                "[" . NextcloudGroupDataAccess::class . "] fetched group with ID: " . $id
            );
        }

        return $ncGroup;
    }

    /**
     * Create a new group
     */
    public function create($displayName): ?IGroup
    {
        // Note: the createGroup() function requires a $gid parameter
        // However, looking at the NC DB, it seems that the gid of a group
        // and its displayName can have the same value, hence here we pass the
        // displayName parameter to createGroup() and don't need to generate
        // a unique gid for a given group during creation
        $createdNcGroup = $this->groupManager->createGroup($displayName);

        if (!isset($createdNcGroup)) {
            $this->logger->error(
                "[" . NextcloudGroupDataAccess::class . "] creation of group with displayName: " . $displayName . " failed"
            );
            return null;
        }

        return $createdNcGroup;
    }

    /**
     * Update an existing group by ID
     * 
     * Note: here, we pass the second parameter, since it carries the data to be updated
     * and we need to pass this data to the group that is to be updated
     */
    public function update(string $id, IGroup $newGroupData): ?IGroup
    {
        $ncGroupToUpdate = $this->groupManager->get($id);

        if (!isset($ncGroupToUpdate)) {
            $this->logger->error(
                "[" . NextcloudGroupDataAccess::class . "] group to be updated with ID: " . $id . " doesn't exist"
            );
            return null;
        }

        if ($newGroupData->getDisplayName() !== null) {
            $ncGroupToUpdate->setDisplayName($newGroupData->getDisplayName());
        }

        if ($newGroupData->getUsers() !== null && !empty($newGroupData->getUsers())) {
            $newNcGroupMembers = [];
            
            foreach ($newGroupData->getUsers() as $newNcGroupMember) {
                // First check if the user is an existing one and only then try to place it as a member of the group
                if ($this->userManager->userExists($newNcGroupMember->getUID())) {
                    $ncUserToAdd = $this->userManager->get($newNcGroupMember->getUID());
                    $newNcGroupMembers[] = $ncUserToAdd;
                } else {
                    $this->logger->error(
                        "[" . NextcloudGroupDataAccess::class . "] user from new group data with ID: " . $id . " doesn't exist"
                    );
                }
            }

            $currentNcGroupMembers = $ncGroupToUpdate->getUsers();
            if (isset($currentNcGroupMembers) && !empty($currentNcGroupMembers)) {
                // If the group can't remove users from itself, then we abort and return null
                if (!$ncGroupToUpdate->canRemoveUser()) {
                    return null;
                }

                // Else, if we can remove users, then we remove all current users
                foreach ($currentNcGroupMembers as $currentNcGroupMember) {
                    $ncGroupToUpdate->removeUser($currentNcGroupMember);
                }
            }

            // After having deleted the current members, we try to replace them with the new ones
            if (!$ncGroupToUpdate->canAddUser()) {
                return null;
            }

            foreach ($newNcGroupMembers as $newNcGroupMember) {
                $ncGroupToUpdate->addUser($newNcGroupMember);
            }
        }

        // Return the now updated NC group
        return $this->groupManager->get($id);
    }

    /**
     * Delete an existing group by ID
     */
    public function delete($id): bool
    {
        $ncGroupToDelete = $this->groupManager->get($id);

        if (!isset($ncGroupToDelete)) {
            $this->logger->error(
                "[" . NextcloudGroupDataAccess::class . "] group to be deleted with ID: " . $id . " doesn't exist"
            );

            return false;
        }

        if ($ncGroupToDelete->delete()) {
            return true;
        }

        $this->logger->error(
            "[" . NextcloudGroupDataAccess::class . "] couldn't delete group with ID: " . $id
        );

        return false;
    }
}
