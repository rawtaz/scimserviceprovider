<?php

$routes = [
	'routes' => [
		['name' => 'service_provider_configuration#resource_types', 'url' => '/ResourceTypes', 'verb' => 'GET'],
		['name' => 'service_provider_configuration#schemas', 'url' => '/Schemas', 'verb' => 'GET'],
		['name' => 'service_provider_configuration#service_provider_config', 'url' => '/ServiceProviderConfig', 'verb' => 'GET'],
	]
];

$config = require dirname(__DIR__) . '/lib/Config/config.php';
$userAndGroupRoutes = [];

if (isset($config['auth_type']) && !empty($config['auth_type']) && (strcmp($config['auth_type'], 'bearer') === 0)) {
	$userAndGroupRoutes = [
			['name' => 'user_bearer#index', 'url' => '/bearer/Users', 'verb' => 'GET'],
			['name' => 'user_bearer#show', 'url' => '/bearer/Users/{id}', 'verb' => 'GET'],
			['name' => 'user_bearer#create', 'url' => '/bearer/Users', 'verb' => 'POST'],
			['name' => 'user_bearer#update', 'url' => '/bearer/Users/{id}', 'verb' => 'PUT'],
			['name' => 'user_bearer#destroy', 'url' => '/bearer/Users/{id}', 'verb' => 'DELETE'],

			['name' => 'group_bearer#index', 'url' => '/bearer/Groups', 'verb' => 'GET'],
			['name' => 'group_bearer#show', 'url' => '/bearer/Groups/{id}', 'verb' => 'GET'],
			['name' => 'group_bearer#create', 'url' => '/bearer/Groups', 'verb' => 'POST'],
			['name' => 'group_bearer#update', 'url' => '/bearer/Groups/{id}', 'verb' => 'PUT'],
			['name' => 'group_bearer#destroy', 'url' => '/bearer/Groups/{id}', 'verb' => 'DELETE'],
	];
} else if (!isset($config['auth_type']) || empty($config['auth_type']) || (strcmp($config['auth_type'], 'basic') === 0)) {
	$userAndGroupRoutes = [
			['name' => 'user#index', 'url' => '/Users', 'verb' => 'GET'],
			['name' => 'user#show', 'url' => '/Users/{id}', 'verb' => 'GET'],
			['name' => 'user#create', 'url' => '/Users', 'verb' => 'POST'],
			['name' => 'user#update', 'url' => '/Users/{id}', 'verb' => 'PUT'],
			['name' => 'user#destroy', 'url' => '/Users/{id}', 'verb' => 'DELETE'],
	
			['name' => 'group#index', 'url' => '/Groups', 'verb' => 'GET'],
			['name' => 'group#show', 'url' => '/Groups/{id}', 'verb' => 'GET'],
			['name' => 'group#create', 'url' => '/Groups', 'verb' => 'POST'],
			['name' => 'group#update', 'url' => '/Groups/{id}', 'verb' => 'PUT'],
			['name' => 'group#destroy', 'url' => '/Groups/{id}', 'verb' => 'DELETE'],
	];
}

$routes['routes'] = array_merge($routes['routes'], $userAndGroupRoutes);

return $routes;
