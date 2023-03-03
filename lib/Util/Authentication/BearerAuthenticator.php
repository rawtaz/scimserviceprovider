<?php

namespace OCA\SCIMServiceProvider\Util\Authentication;

use Exception;
use Opf\ScimServerPhp\Firebase\JWT\JWT;
use Opf\ScimServerPhp\Firebase\JWT\Key;
use OCA\SCIMServiceProvider\Util\Util;
use OCP\IUserManager;
use Opf\Util\Authentication\AuthenticatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class BearerAuthenticator implements AuthenticatorInterface
{
    /** @var \Psr\Log\LoggerInterface */
    private LoggerInterface $logger;

    /** @var \OCP\IUserManager */
    private IUserManager $userManager;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
        $this->userManager = $container->get(IUserManager::class);
    }

    public function authenticate(string $credentials, array $authorizationInfo): bool
    {
        $jwtPayload = [];
        $jwtSecret = Util::getConfigFile()['jwt']['secret'];
        try {
            $jwtPayload = (array) JWT::decode($credentials, new Key($jwtSecret, 'HS256'));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        // If the 'user' claim is missing from the JWT, then auth is considered to have failed
        if (!isset($jwtPayload['user']) || empty($jwtPayload['user'])) {
            $this->logger->error("No \"user\" claim found in JWT");
            return false;
        }

        $username = $jwtPayload['user'];

        // If we managed to find a user with that username, then auth succeeded
        $user = $this->userManager->get($username);
        if ($user !== null) {
            return true;
        }

        $this->logger->error("User with this username doesn't exist");
        return false;
    }
}
