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

abstract class ASCIMGroup extends ApiController {
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
	 * creates an object with all group data
	 *
	 * @param string $groupId
	 * @param bool $includeScopes
	 * @return array
	 * @throws Exception
	 */
	protected function getSCIMGroup(string $groupId): array {
		$groupId = urldecode($groupId);

		// Check the group exists
		$group = $this->groupManager->get($groupId);
		if ($group === null) {
			return [];
		}

		$members = array();
		foreach ($this->groupManager->get($groupId)->getUsers() as $member) {
			$members[] = [
				'value' => $member->getUID(),
				'$ref' => '/Users/' . $member->getUID(),
				'display' => $member->getDisplayName()
			];
		}

		return [
			'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'],
			'id' => $groupId,
			'displayName' => $group->getDisplayName(),
			'externalId' => '1234', // todo
			'meta' => [
				'resourceType' => 'Group',
				'location' => '/Groups/' . $groupId,
				'created' => '2022-04-28T18:27:17.783Z', // todo
				'lastModified' => '2022-04-28T18:27:17.783Z' // todo
			],
			'members' => $members
		];
	}
}
