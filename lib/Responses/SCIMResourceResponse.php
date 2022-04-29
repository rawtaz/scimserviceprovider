<?php

namespace OCA\SCIMServiceProvider\Responses;

use OCP\AppFramework\Http\JSONResponse;

use OCA\SCIMServiceProvider\Exceptions\SCIMException;

/**
 * Class SCIMResourceResponse
 * 
 */
class SCIMResourceResponse extends JSONResponse {
	/**
	 * Returns the rendered json
	 * @return string the rendered json
	 * @since 6.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() {
		$response = json_encode($this->data, JSON_UNESCAPED_SLASHES);

		if ($response === false) {
			throw new SCIMException(sprintf('Could not json_encode due to invalid ' .
				'non UTF-8 characters in the array: %s', var_export($this->data, true)));
		}

		return $response;
	}
}