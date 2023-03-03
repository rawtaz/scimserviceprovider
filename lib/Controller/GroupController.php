<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Controller;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Service\GroupService;

class GroupController extends ApiController
{
    /** @var GroupService */
    private $groupService;

    public function __construct(
        string $appName,
        IRequest $request,
        GroupService $groupService
    ) {
        parent::__construct(
            $appName,
            $request
        );

        $this->groupService = $groupService;
    }

    /**
     * @NoCSRFRequired
     *
     * @param string $filter
     * @return SCIMListResponse
     * returns a list of groups and their data
     */
    public function index(string $filter = ''): SCIMListResponse
    {
        return $this->groupService->getAll($filter);
    }

    /**
     * @NoCSRFRequired
     *
     * gets group info
     *
     * @param string $id
     * @return SCIMJSONResponse
     */
    // TODO: Add filtering support here as well
    public function show(string $id): SCIMJSONResponse
    {
        return $this->groupService->getOneById($id);
    }

    /**
     * @NoCSRFRequired
     *
     * @param string $displayName
     * @param array  $members
     * @return SCIMJSONResponse
     */
    public function create(string $displayName = '', array $members = []): SCIMJSONResponse
    {
        return $this->groupService->create($displayName, $members);
    }

    /**
     * @NoCSRFRequired
     *
     * @param string $id
     *
     * @param string $displayName
     * @param array  $members
     * @return SCIMJSONResponse
     */
    public function update(string $id, string $displayName = '', array $members = []): SCIMJSONResponse
    {
        return $this->groupService->update($id, $displayName, $members);
    }

    /**
     * @NoCSRFRequired
     *
     * @param string $id
     * @return Response
     */
    public function destroy(string $id): Response
    {
        return $this->groupService->destroy($id);
    }
}
