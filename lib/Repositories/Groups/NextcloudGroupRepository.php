<?php

namespace OCA\SCIMServiceProvider\Repositories\Groups;

use Opf\Models\SCIM\Standard\Groups\CoreGroup;
use Opf\Repositories\Repository;
use Opf\Util\Filters\FilterUtil;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class NextcloudGroupRepository extends Repository
{
    /** @var Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->dataAccess = $container->get('GroupDataAccess');
        $this->adapter = $container->get('GroupAdapter');
        $this->logger = $container->get(LoggerInterface::class);
    }

    /**
     * Read all groups in SCIM format
     */
    public function getAll(
        $filter = '',
        $startIndex = 0,
        $count = 0,
        $attributes = [],
        $excludedAttributes = []
    ): array {
        $this->logger->info(
            "[" . NextcloudGroupRepository::class . "] reading all groups"
        );

        // Read all NC groups
        $ncGroups = $this->dataAccess->getAll();
        $scimGroups = [];

        $this->logger->info(
            "[" . NextcloudGroupRepository::class . "] fetched " . count($ncGroups) . " NC groups"
        );

        foreach ($ncGroups as $ncGroup) {
            $scimGroup = $this->adapter->getCoreGroup($ncGroup);
            $scimGroups[] = $scimGroup;
        }

        $this->logger->info(
            "[" . NextcloudGroupRepository::class . "] transformed " . count($scimGroups) . " SCIM groups"
        );

        if (isset($filter) && !empty($filter)) {
            $scimGroupsToFilter = [];
            foreach ($scimGroups as $scimGroup) {
                $scimGroupsToFilter[] = $scimGroup->toSCIM(false);
            }
            
            $filteredScimData = FilterUtil::performFiltering($filter, $scimGroupsToFilter);
            
            $scimGroups = [];
            foreach ($filteredScimData as $filteredScimGroup) {
                $scimGroup = new CoreGroup();
                $scimGroup->fromSCIM($filteredScimGroup);
                $scimGroups[] = $scimGroup;
            }
            
            return $scimGroups;
        }

        return $scimGroups;
    }

    /**
     * Read a single group by ID in SCIM format
     */
    public function getOneById(
        string $id,
        $filter = '',
        $startIndex = 0,
        $count = 0,
        $attributes = [],
        $excludedAttributes = []
    ): ?CoreGroup {
        $this->logger->info(
            "[" . NextcloudGroupRepository::class . "] reading group with ID: " . $id
        );

        $ncGroup = $this->dataAccess->getOneById($id);
        return $this->adapter->getCoreGroup($ncGroup);
    }

    /**
     * Create a group from SCIM data
     */
    public function create($object): ?CoreGroup
    {
        $scimGroupToCreate = new CoreGroup();
        $scimGroupToCreate->fromSCIM($object);

        $displayName = $scimGroupToCreate->getDisplayName();
        $ncGroupCreated = $this->dataAccess->create($displayName);

        $this->logger->info(
            "[" . NextcloudGroupRepository::class . "] creating group with displayName: " . $displayName
        );

        if (isset($ncGroupCreated)) {
            // Set the rest of the properties of the NC group with the adapter
            $ncGroupCreated = $this->adapter->getNCGroup($scimGroupToCreate, $ncGroupCreated);
            return $this->adapter->getCoreGroup($ncGroupCreated);
        }

        $this->logger->error(
            "[" . NextcloudGroupRepository::class . "] creation of group with displayName: " . $displayName . " failed"
        );

        return null;
    }

    /**
     * Update a group by ID from SCIM data
     */
    public function update(string $id, $object): ?CoreGroup
    {
        $this->logger->info(
            "[" . NextcloudGroupRepository::class . "] updating group with ID: " . $id
        );

        $scimGroupToUpdate = new CoreGroup();
        $scimGroupToUpdate->fromSCIM($object);

        $ncGroup = $this->dataAccess->getOneById($id);

        if (isset($ncGroup)) {
            $ncGroupToUpdate = $this->adapter->getNCGroup($scimGroupToUpdate, $ncGroup);
            $ncGroupUpdated = $this->dataAccess->update($id, $ncGroupToUpdate);
    
            if (isset($ncGroupUpdated)) {
                return $this->adapter->getCoreGroup($ncGroupUpdated);
            }
        }

        $this->logger->error(
            "[" . NextcloudGroupRepository::class . "] update of group with ID: " . $id . " failed"
        );

        return null;
    }

    /**
     * Delete a group by ID
     */
    public function delete(string $id): bool
    {
        $this->logger->info(
            "[" . NextcloudGroupRepository::class . "] deleting group with ID: " . $id
        );

        return $this->dataAccess->delete($id);
    }
}
