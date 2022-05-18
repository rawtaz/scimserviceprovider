<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Controller;

use OC\Group\Manager;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\ApiController;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

abstract class ASCIMUser extends ApiController {
	/** @var IUserManager */
	protected $userManager;
	/** @var IConfig */
	protected $config;
	/** @var IGroupManager|Manager */ // FIXME Requires a method that is not on the interface
	protected $groupManager;
	/** @var IUserSession */
	protected $userSession;
	/** @var IAccountManager */
	protected $accountManager;

	public function __construct(string $appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								IGroupManager $groupManager,
								IUserSession $userSession,
								IAccountManager $accountManager) {
		parent::__construct($appName, $request);

		$this->userManager = $userManager;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->accountManager = $accountManager;
	}

	/**
	 * creates an object with all user data
	 *
	 * @param string $userId
	 * @param bool $includeScopes
	 * @return array
	 * @throws Exception
	 */
	protected function getSCIMUser(string $userId): array {
		// Check if the target user exists
		$targetUserObject = $this->userManager->get($userId);
		if ($targetUserObject === null) {
			return [];
		}

		$enabled = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'enabled', 'true') === 'true';

		return [
			'schemas' => ["urn:ietf:params:scim:schemas:core:2.0:User"],
			'id' => $userId,
			'name' => [
				'formatted' => $targetUserObject->getDisplayName()
			],
			'meta' => [
				'resourceType' => 'User',
				'location' => '/Users/' . $userId,
				'created' => '2022-04-28T18:27:17.783Z', // todo
				'lastModified' => '2022-04-28T18:27:17.783Z' // todo
			],
			'userName' => $userId,
			'displayName' => $targetUserObject->getDisplayName(),
			'emails' => [ // todo if no emails
				[
					'primary' => true,
					'value' => $targetUserObject->getSystemEMailAddress()
				]
			],
			'externalId' => '1234', // todo
			'active' => $enabled
		];
	}
}
