<?php

declare(strict_types=1);

namespace OCA\SCIMServiceProvider\Middleware;

use Exception;
use OCA\SCIMServiceProvider\Exception\ContentTypeException;
use OCA\SCIMServiceProvider\Responses\SCIMErrorResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use Psr\Container\ContainerInterface;

class ContentTypeMiddleware extends Middleware
{
    /** @var IRequest */
    private $request;

    public function __construct(ContainerInterface $container)
    {
        $this->request = $container->get(IRequest::class);
    }
    
    public function beforeController($controller, $methodName)
    {
        $requestMethod = $this->request->getMethod();

        // If the incoming request is POST or PUT => check the Content-Type header and the request body
        if (in_array(strtolower($requestMethod), array("post", "put"))) {
            $contentTypeHeader = $this->request->getHeader("Content-Type");
            if (!isset($contentTypeHeader) || empty($contentTypeHeader)) {
                throw new ContentTypeException("Content-Type header not set");
            }

            // Accept both "application/scim+json" and "application/json" as valid headers
            // See https://www.rfc-editor.org/rfc/rfc7644.html#section-3.8
            if (
                strpos($contentTypeHeader, "application/scim+json") === false
                && strpos($contentTypeHeader, "application/json") === false
            ) {
                throw new ContentTypeException("Content-Type header is not application/scim+json or application/json");
            }

            // Verify that the request body is indeed valid JSON
            $requestBody = $this->request->getParams();
            if (isset($requestBody) && !empty($requestBody)) {
                $requestBody = array_keys($requestBody)[0];

                if (json_decode($requestBody) === false) {
                    throw new ContentTypeException("Request body is not valid JSON");
                }
            }
        }
    }

    public function afterException($controller, $methodName, Exception $exception)
    {
        return new SCIMErrorResponse(['message' => $exception->getMessage()], 400);
    }
}