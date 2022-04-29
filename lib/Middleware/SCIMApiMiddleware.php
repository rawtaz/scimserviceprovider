<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\SCIMServiceProvider\Middleware;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCA\SCIMServiceProvider\Responses\SCIMResourceResponse;

class SCIMApiMiddleware extends Middleware {

	/** @var IControllerMethodReflector */
	private $reflector;

	/** @var bool */
	private $isAdmin;

	/** @var bool */
	private $isSubAdmin;

	/**
	 * ProvisioningApiMiddleware constructor.
	 *
	 * @param IControllerMethodReflector $reflector
	 * @param bool $isAdmin
	 * @param bool $isSubAdmin
	 */
	public function __construct(
		IControllerMethodReflector $reflector,
		bool $isAdmin,
		bool $isSubAdmin) {
		$this->reflector = $reflector;
		$this->isAdmin = $isAdmin;
		$this->isSubAdmin = $isSubAdmin;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return SCIMResourceResponse
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof SCIMException) {
			return SCIMResourceResponse($exception->getMessage(), 500);
		}
		return SCIMResourceResponse([
			"schema" => "error"
		],500);
	}
}
