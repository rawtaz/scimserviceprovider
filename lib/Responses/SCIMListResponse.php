<?php

namespace OCA\SCIMServiceProvider\Responses;

use OCP\AppFramework\Http\Response;

use OCA\SCIMServiceProvider\Exceptions\SCIMException;

/**
 * Class SCIMListResponse
 *
 */
class SCIMListResponse extends Response {
	/**
	 * response data
	 * @var array|object
	 */
	protected $data;


	/**
	 * constructor of SCIMListResponse
	 * @param array|object $data the object or array that should be transformed
	 * @param int $statusCode the Http status code, defaults to 200
	 * @since 6.0.0
	 */
	public function __construct($data = [], $statusCode = 200) {
		parent::__construct();

		$this->data = $data;
		$this->setStatus($statusCode);
		$this->addHeader('Content-Type', 'application/scim+json; charset=utf-8');
	}

	/**
	 * Returns the rendered json
	 * @return string the rendered json
	 * @since 6.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() {
		$scimReponse = [
			'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
			'startIndex' => 1, // todo pagination
			'Resources' => $this->data,
			'totalResults' => sizeof($this->data)
		];
		$response = json_encode($scimReponse, JSON_UNESCAPED_SLASHES);

		if ($response === false) {
			throw new SCIMException(sprintf('Could not json_encode due to invalid ' .
				'non UTF-8 characters in the array: %s', var_export($scimReponse, true)));
		}

		return $response;
	}
}
