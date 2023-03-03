<?php

namespace OCA\SCIMServiceProvider\Util;

class Util
{
    public const SCIM_APP_URL_PATH = "index.php/apps/scimserviceprovider";

    public static function getConfigFile()
    {
        $configFilePath = dirname(__DIR__) . '/Config/config.php';
        $config = require($configFilePath);

        return $config;
    }
}
