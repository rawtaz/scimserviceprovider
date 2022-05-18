<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Controller;

use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;

class GroupController extends ASCIMGroup {

	/** @var LoggerInterface */
	private $logger;

	public function __construct(string $appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								IGroupManager $groupManager,
								IUserSession $userSession,
								IAccountManager $accountManager,
								LoggerInterface $logger) {
		parent::__construct($appName,
							$request,
							$userManager,
							$config,
							$groupManager,
							$userSession,
							$accountManager);

		$this->logger = $logger;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * returns a list of groups and their data
	 */
	public function index(): SCIMListResponse {
		$SCIMGroups = $this->groupManager->search('', null, 0);
		$SCIMGroups = array_map(function ($group) {
			return $this->getSCIMGroup($group->getGID());
		}, $SCIMGroups);
		return new SCIMListResponse($SCIMGroups);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * gets group info
	 *
	 * @param string $id
	 * @return SCIMJSONResponse
	 * @throws Exception
	 */
	public function show(string $id): SCIMJSONResponse {
		$group = $this->getSCIMGroup($id);
		// getUserData returns empty array if not enough permissions
		if (empty($group)) {
			return new SCIMErrorResponse(['message' => 'Group not found'], 404);
		}
		return new SCIMJSONResponse($group);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param string $displayName
	 * @param array  $members
	 * @return SCIMJSONResponse
	 * @throws Exception
	 */
	public function create(string $displayName = '',
							array  $members = []): SCIMJSONResponse {
		$id = urlencode($displayName);
		// Validate name
		if (empty($id)) {
			$this->logger->error('Group name not supplied', ['app' => 'provisioning_api']);
			return new SCIMErrorResponse(['message' => 'Invalid group name'], 400);
		}
		// Check if it exists
		if ($this->groupManager->groupExists($id)) {
			return new SCIMErrorResponse(['message' => 'Group exists'], 409);
		}
		$group = $this->groupManager->createGroup($id);
		if ($group === null) {
			return new SCIMErrorResponse(['message' => 'Not supported by backend'], 103);
		}
		$group->setDisplayName($displayName);
		foreach ($members as $member) {
			$this->logger->error('Group name not supplied' . $member['value'], ['app' => 'provisioning_api']);
			$targetUser = $this->userManager->get($member['value']);
			$group->addUser($targetUser);
		}
		return new SCIMJSONResponse($this->getSCIMGroup($id));
	}


	/**
	 * @NoCSRFRequired
	 *
	 * @param string $id
	 *
	 * @param string $displayName
	 * @param array  $members
	 * @return DataResponse
	 * @throws Exception
	 */
	public function update(string $id,
							string $displayName = '',
							array  $members = []): SCIMJSONResponse {
		$group = $this->groupManager->get($id);
		if (!$this->groupManager->groupExists($id)) {
			return new SCIMErrorResponse(['message' => 'Group not found'], 404);
		}
		foreach ($members as $member) {
			$targetUser = $this->userManager->get($member['value']);
			$group->addUser($targetUser);
			// todo implement member removal (:
		}
		return new SCIMJSONResponse($this->getSCIMGroup($id));
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function destroy(string $id): Response {
		$groupId = urldecode($id);

		// Check it exists
		if (!$this->groupManager->groupExists($groupId)) {
			return new SCIMErrorResponse(['message' => 'Group not found'], 404);
		} elseif ($groupId === 'admin' || !$this->groupManager->get($groupId)->delete()) {
			// Cannot delete admin group
			return new SCIMErrorResponse(['message' => 'Can\'t delete this group, not enough rights or admin group'], 403);
		}
		$response = new Response();
		$response->setStatus(204);
		return $response;
	}
}
