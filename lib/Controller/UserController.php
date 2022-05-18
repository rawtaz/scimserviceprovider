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
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;

class UserController extends ASCIMUser {

	/** @var LoggerInterface */
	private $logger;
	/** @var ISecureRandom */
	private $secureRandom;


	public function __construct(string $appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								IGroupManager $groupManager,
								IUserSession $userSession,
								IAccountManager $accountManager,
								LoggerInterface $logger,
								ISecureRandom $secureRandom) {
		parent::__construct($appName,
							$request,
							$userManager,
							$config,
							$groupManager,
							$userSession,
							$accountManager);

		$this->logger = $logger;
		$this->secureRandom = $secureRandom;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * returns a list of users and their data
	 */
	public function index(): SCIMListResponse {
		$users = [];
		$users = $this->userManager->search('', null, 0);
		$userIds = array_keys($users);

		$SCIMUsers = array();
		foreach ($userIds as $userId) {
			$userId = (string) $userId;
			$SCIMUser = $this->getSCIMUser($userId);
			// Do not insert empty entry
			if (!empty($SCIMUser)) {
				$SCIMUsers[] = $SCIMUser;
			}
		}

		return new SCIMListResponse($SCIMUsers);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * gets user info
	 *
	 * @param string $id
	 * @return SCIMJSONResponse
	 * @throws Exception
	 */
	public function show(string $id): SCIMJSONResponse {
		$user = $this->getSCIMUser($id);
		// getUserData returns empty array if not enough permissions
		if (empty($user)) {
			return new SCIMErrorResponse(['message' => 'User not found'], 404);
		}
		return new SCIMJSONResponse($user);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param bool   $active
	 * @param string $displayName
	 * @param array  $emails
	 * @param string $userName
	 * @return SCIMJSONResponse
	 * @throws Exception
	 */
	public function create(bool   $active = true,
							string $displayName = '',
							array  $emails = [],
							string $userName = ''): SCIMJSONResponse {
		if ($this->userManager->userExists($userName)) {
			$this->logger->error('Failed createUser attempt: User already exists.', ['app' => 'SCIMServiceProvider']);
			return new SCIMErrorResponse(['message' => 'User already exists'], 409);
		}

		try {
			$newUser = $this->userManager->createUser($userName, $this->secureRandom->generate(64));
			$this->logger->info('Successful createUser call with userid: ' . $userName, ['app' => 'SCIMServiceProvider']);
			foreach ($emails as $email) {
				$this->logger->error('Log email: ' . $email['value'], ['app' => 'SCIMServiceProvider']);
				if ($email['primary'] === true) {
					$newUser->setEMailAddress($email['value']);
				}
			}
			$newUser->setEnabled($active);
			return new SCIMJSONResponse($this->getSCIMUser($userName));
		} catch (Exception $e) {
			$this->logger->warning('Failed createUser attempt with SCIMException exeption.', ['app' => 'SCIMServiceProvider']);
			throw $e;
		}
	}


	/**
	 * @NoCSRFRequired
	 *
	 * @param string $id
	 *
	 * @param bool   $active
	 * @param string $displayName
	 * @param array  $emails
	 * @return DataResponse
	 * @throws Exception
	 */
	public function update(string $id,
							bool   $active,
							string $displayName = '',
							array  $emails = []): SCIMJSONResponse {
		$targetUser = $this->userManager->get($id);
		if ($targetUser === null) {
			return new SCIMErrorResponse(['message' => 'User not found'], 404);
		}
		foreach ($emails as $email) {
			if ($email['primary'] === true) {
				$targetUser->setEMailAddress($email['value']);
			}
		}
		if (isset($active)) {
			$targetUser->setEnabled($active);
		}
		return new SCIMJSONResponse($this->getSCIMUser($id));
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function destroy(string $id): Response {
		$targetUser = $this->userManager->get($id);

		if ($targetUser === null) {
			return new SCIMErrorResponse(['message' => 'User not found'], 404);
		}

		// Go ahead with the delete
		if ($targetUser->delete()) {
			$response = new Response();
			$response->setStatus(204);
			return $response;
		} else {
			return new SCIMErrorResponse(['message' => 'Couldn\'t delete user'], 503);
		}
	}
}
