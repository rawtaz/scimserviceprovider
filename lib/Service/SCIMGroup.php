<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Service;

use OC\Group\Manager;
use OCP\IGroupManager;

class SCIMGroup {
	/** @var IGroupManager|Manager */ // FIXME Requires a method that is not on the interface
	protected $groupManager;

	public function __construct(IGroupManager $groupManager) {
		$this->groupManager = $groupManager;
	}

	/**
	 * creates an object with all group data
	 *
	 * @param string $groupId
	 * @param bool $includeScopes
	 * @return array
	 * @throws Exception
	 */
	public function get(string $groupId): array {
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
				'created' => '1970-01-01T00:00:00.000Z',
				'lastModified' => '1970-01-01T00:00:00.000Z'
			],
			'members' => $members
		];
	}
}
