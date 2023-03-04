<?php

namespace OCA\SCIMServiceProvider\AppInfo;

use Error;
use OCA\SCIMServiceProvider\Adapter\Groups\NextcloudGroupAdapter;
use OCA\SCIMServiceProvider\Adapter\Users\NextcloudUserAdapter;
use OCA\SCIMServiceProvider\Controller\GroupBearerController;
use OCA\SCIMServiceProvider\Controller\GroupController;
use OCA\SCIMServiceProvider\Controller\UserBearerController;
use OCA\SCIMServiceProvider\Controller\UserController;
use OCA\SCIMServiceProvider\DataAccess\Groups\NextcloudGroupDataAccess;
use OCA\SCIMServiceProvider\DataAccess\Users\NextcloudUserDataAccess;
use OCA\SCIMServiceProvider\Middleware\BearerAuthMiddleware;
use OCA\SCIMServiceProvider\Repositories\Groups\NextcloudGroupRepository;
use OCA\SCIMServiceProvider\Repositories\Users\NextcloudUserRepository;
use OCA\SCIMServiceProvider\Service\GroupService;
use OCA\SCIMServiceProvider\Service\SCIMGroup;
use OCA\SCIMServiceProvider\Service\SCIMUser;
use OCA\SCIMServiceProvider\Service\UserService;
use OCA\SCIMServiceProvider\Util\Authentication\BearerAuthenticator;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Opf\Util\Util;
use Psr\Container\ContainerInterface;

/**
 * The main entry point of the entire application
 */
class Application extends App implements IBootstrap
{
    public const APP_ID = 'scimserviceprovider';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    /**
     * This method is used for registering services, needed as dependencies via dependency injection (DI)
     * 
     * Note: "service" here means simply a class that is needed as a dependency somewhere
     * and needs to be injected as such via a DI container (as per PSR-11)
     */
    public function register(IRegistrationContext $context): void
    {
        require realpath(dirname(__DIR__) . '/../vendor/autoload.php');
        $config = require dirname(__DIR__) . '/Config/config.php';
        $context->registerService('SCIMUser', function(ContainerInterface $c) {
            return new SCIMUser(
                $c->get(IUserManager::class),
                $c->get(IConfig::class)
            );
        });

        $context->registerService(UserService::class, function(ContainerInterface $c) {
            return new UserService($c);
        });

        $context->registerService(GroupService::class, function(ContainerInterface $c) {
            return new GroupService($c);
        });

        $context->registerService('UserRepository', function(ContainerInterface $c) {
            return new NextcloudUserRepository($c);
        });

        $context->registerService('UserAdapter', function(ContainerInterface $c) {
            return new NextcloudUserAdapter($c);
        });

        $context->registerService('UserDataAccess', function(ContainerInterface $c) {
            return new NextcloudUserDataAccess($c);
        });


        $context->registerService('SCIMGroup', function(ContainerInterface $c) {
            return new SCIMGroup(
                $c->get(IGroupManager::class)
            );
        });

        $context->registerService('GroupRepository', function(ContainerInterface $c) {
            return new NextcloudGroupRepository($c);
        });

        $context->registerService('GroupAdapter', function(ContainerInterface $c) {
            return new NextcloudGroupAdapter($c);
        });

        $context->registerService('GroupDataAccess', function(ContainerInterface $c) {
            return new NextcloudGroupDataAccess($c);
        });

        if (isset($config['auth_type']) && !empty($config['auth_type']) && (strcmp($config['auth_type'], 'bearer') === 0)) {
            // If the auth_type is set to "bearer", then use Bearer token endpoints
            // For bearer tokens, we also need to register the bearer token auth middleware
            $context->registerService(BearerAuthenticator::class, function(ContainerInterface $c) {
                return new BearerAuthenticator($c);
            });
            
            $context->registerService(BearerAuthMiddleware::class, function(ContainerInterface $c) {
                return new BearerAuthMiddleware($c);
            });

            $context->registerMiddleware(BearerAuthMiddleware::class);
            
            $context->registerService(UserBearerController::class, function (ContainerInterface $c) {
                return new UserBearerController(
                    self::APP_ID,
                    $c->get(IRequest::class),
                    $c->get(UserService::class)
                );
            });

            $context->registerService(GroupBearerController::class, function (ContainerInterface $c) {
                return new GroupBearerController(
                    self::APP_ID,
                    $c->get(IRequest::class),
                    $c->get(GroupService::class)
                );
            });
        } else if (!isset($config['auth_type']) || empty($config['auth_type']) || (strcmp($config['auth_type'], 'basic') === 0)) {
            // Otherwise, if auth_type is set to "basic" or if it's not set at all, use Basic auth
            $context->registerService(UserController::class, function (ContainerInterface $c) {
                return new UserController(
                    self::APP_ID,
                    $c->get(IRequest::class),
                    $c->get(UserService::class)
                );
            });

            $context->registerService(GroupController::class, function (ContainerInterface $c) {
                return new GroupController(
                    self::APP_ID,
                    $c->get(IRequest::class),
                    $c->get(GroupService::class)
                );
            });
        } else {
            // In the case of any other auth_type value, complain with an error message
            throw new Error("Unknown auth type was set in config file");
        }
    }

    /**
     * This method is called for starting (i.e., booting) the application
     * 
     * Note: here the method body is empty, since we don't need to do any extra work in it
     */
    public function boot(IBootContext $context): void
    {
    }
}
