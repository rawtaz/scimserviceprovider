<?php

namespace OCA\SCIMServiceProvider\Responses;

use OCP\AppFramework\Http\Response;

/**
 * Class SCIMResourceResponse
 * 
 */
class SCIMJSONResponse extends Response {
	/**
	 * response data
	 * @var array|object
	 */
	protected $data;


	/**
	 * constructor of SCIMResourceResponse
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
		$response = json_encode($this->data, JSON_UNESCAPED_SLASHES);

		if ($response === false) {
			throw new Exception(sprintf('Could not json_encode due to invalid ' .
				'non UTF-8 characters in the array: %s', var_export($this->data, true)));
		}

		return $response;
	}
}