<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\SCIMServiceProvider\Controller;

use OC\Group\Manager;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;

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
