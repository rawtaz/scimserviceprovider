<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Controller;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Service\UserService;

class UserBearerController extends ApiController
{
    /** @var UserService */
    private $userService;


    public function __construct(
        string $appName,
        IRequest $request,
        UserService $userService
    ) {
        parent::__construct(
            $appName,
            $request
        );

        $this->userService = $userService;
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     *
     * @param string $filter
     * @return SCIMListResponse
     * returns a list of users and their data
     */
    public function index(string $filter = ''): SCIMListResponse
    {
        return $this->userService->getAll($filter);
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     *
     * gets user info
     *
     * @param string $id
     * @return SCIMJSONResponse
     */
    // TODO: Add filtering support here as well
    public function show(string $id): SCIMJSONResponse
    {
        return $this->userService->getOneById($id);
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     *
     * @param bool   $active
     * @param string $displayName
     * @param array  $emails
     * @param string $externalId
     * @param string $userName
     * @return SCIMJSONResponse
     */
    public function create(
        bool $active = true,
        string $displayName = '',
        array $emails = [],
        string $externalId = '',
        string $userName = ''
    ): SCIMJSONResponse
    {
        return $this->userService->create(
            $active,
            $displayName,
            $emails,
            $externalId,
            $userName
        );
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     *
     * @param string $id
     *
     * @param bool   $active
     * @param string $displayName
     * @param array  $emails
     * @return SCIMJSONResponse
     */
    public function update(
        string $id,
        bool $active,
        string $displayName = '',
        array $emails = []
    ): SCIMJSONResponse
    {
        return $this->userService->update($id, $active, $displayName, $emails);
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     *
     * @param string $id
     *
     * @param array  $operations
     * @return SCIMJSONResponse
     */
    public function patch(
        string $id,
        array $Operations
    ): SCIMJSONResponse
    {
        return $this->userService->patch($id, $Operations);
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     *
     * @param string $id
     * @return Response
     */
    public function destroy(string $id): Response
    {
        return $this->userService->destroy($id);
    }
}
