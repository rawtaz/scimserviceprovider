<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Service;

use Exception;
use OCA\SCIMServiceProvider\AppInfo\Application;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;
use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Util\Util;
use OCP\AppFramework\Http\Response;
use OCP\IGroupManager;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GroupService
{
    /** @var LoggerInterface */
    private $logger;

    /** @var \OCA\SCIMServiceProvider\Repositories\Groups\NextcloudGroupRepository */
    private $repository;

    /** @var IGroupManager */
    private $groupManager;

    /** @var IRequest */
    private $request;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
        $this->repository = $container->get('GroupRepository');
        $this->groupManager = $container->get(IGroupManager::class);
        $this->request = $container->get(IRequest::class);
    }

    public function getAll(string $filter = ''): SCIMListResponse
    {
        $this->logger->info("Reading all groups");

        $baseUrl = $this->request->getServerProtocol() . "://"
            . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

        $groups = $this->repository->getAll($filter);

        $scimGroups = [];
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $scimGroups[] = $group->toSCIM(false, $baseUrl);
            }
        }

        return new SCIMListResponse($scimGroups);
    }
    
    public function getOneById(string $id): SCIMJSONResponse
    {
        $this->logger->info("Reading group with ID: " . $id);

        $baseUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

        $group = $this->repository->getOneById($id);
        if (!isset($group) || empty($group)) {
            $this->logger->error("Group with ID " . $id . " not found");
            return new SCIMErrorResponse(['message' => 'Group not found'], 404);
        }
        return new SCIMJSONResponse($group->toSCIM(false, $baseUrl));
    }

    public function create(string $displayName = '', array $members = []): SCIMJSONResponse
    {
        $id = urlencode($displayName);
        // Validate name
        if (empty($id)) {
            $this->logger->error('Group name not supplied', ['app' => 'provisioning_api']);
            return new SCIMErrorResponse(['message' => 'Invalid group name'], 400);
        }
        // Check if it exists
        if ($this->groupManager->groupExists($id)) {
            $this->logger->error("Group to be created already exists");
            return new SCIMErrorResponse(['message' => 'Group exists'], 409);
        }

        try {
            $this->logger->info("Creating group with displayName: " . $displayName);

            $baseUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

            $data = [
                'displayName' => $displayName,
                'members' => $members
            ];

            $createdGroup = $this->repository->create($data);
            if (isset($createdGroup) && !empty($createdGroup)) {
                return new SCIMJSONResponse($createdGroup->toSCIM(false, $baseUrl), 201);
            } else {
                $this->logger->error("Creating group failed");
                return new SCIMErrorResponse(['message' => 'Creating group failed'], 400);
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed createGroup attempt with SCIMException exception.', ['app' => Application::APP_ID]);
            throw $e;
        }
    }

    public function update(string $id, string $displayName = '', array $members = []): SCIMJSONResponse
    {
        $this->logger->info("Updating group with ID: " . $id);

        $baseUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

        $group = $this->repository->getOneById($id);
        if (!isset($group) || empty($group)) {
            $this->logger->error("Group with ID " . $id . " not found for update");
            return new SCIMErrorResponse(['message' => 'Group not found'], 404);
        }

        $data = [
            'displayName' => $displayName,
            'members' => $members
        ];

        $updatedGroup = $this->repository->update($id, $data);
        if (isset($updatedGroup) && !empty($updatedGroup)) {
            return new SCIMJSONResponse($updatedGroup->toSCIM(false, $baseUrl));
        } else {
            $this->logger->error("Updating group with ID " . $id . " failed");
            return new SCIMErrorResponse(['message' => 'Updating group failed'], 400);
        }
    }

    public function destroy(string $id): Response
    {
        $this->logger->info("Deleting group with ID: " . $id);

        if ($id === 'admin') {
            // Cannot delete admin group
            $this->logger->error("Deleting admin group is not allowed");
            return new SCIMErrorResponse(['message' => 'Can\'t delete admin group'], 403);
        }

        $deleteRes = $this->repository->delete($id);

        if ($deleteRes) {
            $response = new Response();
            $response->setStatus(204);
            return $response;
        } else {
            $this->logger->error("Deletion of group with ID " . $id . " failed");
            return new SCIMErrorResponse(['message' => 'Couldn\'t delete group'], 503);
        }
    }
}
