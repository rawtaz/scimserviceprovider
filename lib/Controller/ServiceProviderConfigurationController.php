<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Controller;

use OCA\SCIMServiceProvider\Responses\SCIMJSONResponse;
use OCA\SCIMServiceProvider\Responses\SCIMListResponse;
use OCA\SCIMServiceProvider\Util\Util;
use OCP\AppFramework\ApiController;
use OCP\IRequest;
use Opf\Util\Util as SCIMUtil;
use Psr\Log\LoggerInterface;

class ServiceProviderConfigurationController extends ApiController
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $appName,
                                IRequest $request,
                                LoggerInterface $logger) {
        parent::__construct($appName,
                            $request);
        $this->logger = $logger;
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     */
    public function resourceTypes(): SCIMListResponse
    {
        $baseUrl =
            $this->request->getServerProtocol() . "://"
            . $this->request->getServerHost() . "/"
            . Util::SCIM_APP_URL_PATH;
        $resourceTypes = SCIMUtil::getResourceTypes($baseUrl);
        return new SCIMListResponse($resourceTypes);
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     */
    public function schemas(): SCIMListResponse
    {
        $schemas = SCIMUtil::getSchemas();
        return new SCIMListResponse($schemas);
    }

    /**
     * @NoCSRFRequired
     * @PublicPage
     */
    public function serviceProviderConfig(): SCIMJSONResponse
    {
        $serviceProviderConfig = SCIMUtil::getServiceProviderConfig();
        return new SCIMJSONResponse($serviceProviderConfig);
    }
}
