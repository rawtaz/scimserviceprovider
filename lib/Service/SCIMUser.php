<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Service;

use OCP\IConfig;
use OCP\IUserManager;

class SCIMUser {
	/** @var IUserManager */
	protected $userManager;
	/** @var IConfig */
	protected $config;

	public function __construct(IUserManager $userManager,
								IConfig $config) {
		$this->userManager = $userManager;
		$this->config = $config;
	}

	/**
	 * creates an object with all user data
	 *
	 * @param string $userId
	 * @param bool $includeScopes
	 * @return array
	 * @throws Exception
	 */
	public function get(string $userId): array {
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
				'created' => '1970-01-01T00:00:00.000Z',
				'lastModified' => '1970-01-01T00:00:00.000Z'
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
