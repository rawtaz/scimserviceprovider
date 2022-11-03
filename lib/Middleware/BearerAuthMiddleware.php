<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Middleware;

use Exception;
use OCA\SCIMServiceProvider\Controller\UserController;
use OCA\SCIMServiceProvider\Exception\AuthException;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;
use OCA\SCIMServiceProvider\Util\Authentication\BearerAuthenticator;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use Psr\Container\ContainerInterface;

class BearerAuthMiddleware extends Middleware
{
    /** @var IRequest */
    private IRequest $request;

    /** @var \OCA\SCIMServiceProvider\Util\Authentication\BearerAuthenticator */
    private BearerAuthenticator $bearerAuthenticator;

    public function __construct(ContainerInterface $container)
    {
        $this->request = $container->get(IRequest::class);
        $this->bearerAuthenticator = $container->get(BearerAuthenticator::class);
    }

    public function beforeController($controller, $methodName)
    {
        $authHeader = $this->request->getHeader('Authorization');
        if (empty($authHeader)) {
            throw new AuthException("No Authorization header supplied");
        }
        
        $authHeaderSplit = explode(' ', $authHeader);
        if (count($authHeaderSplit) !== 2 || strcmp($authHeaderSplit[0], "Bearer") !== 0) {
            throw new AuthException("Incorrect Bearer token format");
        }

        $token = $authHeaderSplit[1];

        // Currently the second parameter to authenticate() is an empty array
        // (the second parameter is meant to carry authorization information)
        if (!$this->bearerAuthenticator->authenticate($token, [])) {
            throw new AuthException("Bearer token is invalid");
        }
    }

    public function afterException($controller, $methodName, Exception $exception)
    {
        if ($exception instanceof AuthException) {
            return new SCIMErrorResponse(['message' => $exception->getMessage()], 401);
        }
    }
}
