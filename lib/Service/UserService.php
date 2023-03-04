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
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserService
{
    /** @var LoggerInterface */
    private $logger;

    /** @var \OCA\SCIMServiceProvider\Repositories\Users\NextcloudUserRepository */
    private $repository;

    /** @var IRequest */
    private $request;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
        $this->repository = $container->get('UserRepository');
        $this->request = $container->get(IRequest::class);
    }

    public function getAll(string $filter = ''): SCIMListResponse
    {
        $this->logger->info("Reading all users");

        $baseUrl = $this->request->getServerProtocol() . "://"
            . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

        $users = $this->repository->getAll($filter);

        $scimUsers = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $scimUsers[] = $user->toSCIM(false, $baseUrl);
            }
        }

        return new SCIMListResponse($scimUsers);
    }

    public function getOneById(string $id): SCIMJSONResponse
    {
        $this->logger->info("Reading user with ID: " . $id);

        $baseUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

        $user = $this->repository->getOneById($id);
        if (!isset($user) || empty($user)) {
            $this->logger->error("User with ID " . $id . " not found");
            return new SCIMErrorResponse(['message' => 'User not found'], 404);
        }
        return new SCIMJSONResponse($user->toSCIM(false, $baseUrl));
    }

    public function create(
        bool $active = true,
        string $displayName = '',
        array $emails = [],
        string $externalId = '',
        string $userName = ''
    ): SCIMJSONResponse
    {
        try {
            $this->logger->info("Creating user with userName: " . $userName);

            $baseUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

            $data = [
                'active' => $active,
                'displayName' => $displayName,
                'emails' => $emails,
                'externalId' => $externalId,
                'userName' => $userName
            ];

            $createdUser = $this->repository->create($data);
            if (isset($createdUser) && !empty($createdUser)) {
                return new SCIMJSONResponse($createdUser->toSCIM(false, $baseUrl), 201);
            } else {
                $this->logger->error("Creating user failed");
                return new SCIMErrorResponse(['message' => 'Creating user failed'], 400);
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed createUser attempt with SCIMException exeption.', ['app' => Application::APP_ID]);
            throw $e;
        }
    }

    public function update(
        string $id,
        bool $active,
        string $displayName = '',
        array $emails = []
    ): SCIMJSONResponse
    {
        $this->logger->info("Updating user with ID: " . $id);

        $baseUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . Util::SCIM_APP_URL_PATH;

        $user = $this->repository->getOneById($id);
        if (!isset($user) || empty($user)) {
            $this->logger->error("User with ID " . $id . " not found for update");
            return new SCIMErrorResponse(['message' => 'User not found'], 404);
        }

        $data = [
            'active' => $active,
            'displayName' => $displayName,
            'emails' => $emails
        ];

        $updatedUser = $this->repository->update($id, $data);
        if (isset($updatedUser) && !empty($updatedUser)) {
            return new SCIMJSONResponse($updatedUser->toSCIM(false, $baseUrl));
        } else {
            $this->logger->error("Updating user with ID " . $id . " failed");
            return new SCIMErrorResponse(['message' => 'Updating user failed'], 400);
        }
    }

    public function destroy(string $id): Response
    {
        $this->logger->info("Deleting user with ID: " . $id);

        $deleteRes = $this->repository->delete($id);

        if ($deleteRes) {
            $response = new Response();
            $response->setStatus(204);
            return $response;
        } else {
            $this->logger->error("Deletion of user with ID " . $id . " failed");
            return new SCIMErrorResponse(['message' => 'Couldn\'t delete user'], 503);
        }
    }
}
