<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Controller;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;

use OCA\SCIMServiceProvider\Service\SCIMUser;


class UserController extends ApiController {

	/** @var LoggerInterface */
	private $logger;
	/** @var ISecureRandom */
	private $secureRandom;
	private $SCIMUser;


	public function __construct(string $appName,
								IRequest $request,
								IUserManager $userManager,
								LoggerInterface $logger,
								ISecureRandom $secureRandom,
								SCIMUser $SCIMUser) {
		parent::__construct($appName,
							$request,
							$userManager);

		$this->logger = $logger;
		$this->secureRandom = $secureRandom;
		$this->SCIMUser = $SCIMUser;
		$this->userManager = $userManager;
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
			$SCIMUser = $this->SCIMUser->get($userId);
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
		$user = $this->SCIMUser->get($id);
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
	 * @param string $externalId
	 * @param string $userName
	 * @return SCIMJSONResponse
	 * @throws Exception
	 */
	public function create(bool   $active = true,
							string $displayName = '',
							array  $emails = [],
							string $externalId = '',
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
			$this->SCIMUser->setExternalId($userName, $externalId);
			return new SCIMJSONResponse($this->SCIMUser->get($userName));
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
		return new SCIMJSONResponse($this->SCIMUser->get($id));
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
