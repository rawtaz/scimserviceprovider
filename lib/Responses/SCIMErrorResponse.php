<?php

namespace OCA\SCIMServiceProvider\Responses;

use OCP\AppFramework\Http\Response;

/**
 * Class SCIMErrorResponse
 *
 */
class SCIMErrorResponse extends SCIMJSONResponse {
	/**
	 * response data
	 * @var array|object
	 */
	protected $data;

	/**
	 * Returns the rendered json
	 * @return string the rendered json
	 * @since 6.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() {
		$message = [
			'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
			'detail' => $this->data['message'],
			'scimType' => '',
			'status' => $this->getStatus()
		];
		$response = json_encode($message, JSON_UNESCAPED_SLASHES);

		if ($response === false) {
			throw new Exception(sprintf('Could not json_encode due to invalid ' .
				'non UTF-8 characters in the array: %s', var_export($this->data, true)));
		}

		return $response;
	}
}
