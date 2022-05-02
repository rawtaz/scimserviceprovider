<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author michag86 <micha_g@arcor.de>
 * @author Mikael Hammarin <mikael@try2.se>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith Haridasan <sujith.h@gmail.com>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\SCIMServiceProvider\Controller;

use InvalidArgumentException;
use OC\HintException;
use OC\KnownUser\KnownUserService;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;


class GroupController extends ASCIMGroup {

	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var LoggerInterface */
	private $logger;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var KnownUserService */
	private $knownUserService;
	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(string $appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								IGroupManager $groupManager,
								IUserSession $userSession,
								IAccountManager $accountManager,
								IURLGenerator $urlGenerator,
								LoggerInterface $logger,
								ISecureRandom $secureRandom,
								KnownUserService $knownUserService,
								IEventDispatcher $eventDispatcher) {
		parent::__construct($appName,
							$request,
							$userManager,
							$config,
							$groupManager,
							$userSession,
							$accountManager);

		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->secureRandom = $secureRandom;
		$this->knownUserService = $knownUserService;
		$this->eventDispatcher = $eventDispatcher;
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
	public function create( string $displayName = '',
							array  $members = []): SCIMJSONResponse {
		
		$id=urldecode($displayName);
		// Validate name
		if (empty($id)) {
			$this->logger->error('Group name not supplied', ['app' => 'provisioning_api']);
			return new SCIMErrorResponse(['message' => 'Invalid group name'], 101);
		}
		// Check if it exists
		if ($this->groupManager->groupExists($id)) {
			return new SCIMErrorResponse(['message' => 'Group exists'], 409);
		}
		$group = $this->groupManager->createGroup($id);
		if ($group === null) {
			return new SCIMErrorResponse(['message' => 'Not supported by backend'], 103);
		}
		if ($displayname !== '') {
			$group->setDisplayName($displayName);
		}
		foreach ($members as $member) {
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
	 * @param bool   $active
	 * @param string $displayName
     * @param array  $emails
	 * @return DataResponse
	 * @throws Exception
	 */
	public function update( string $id,
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
