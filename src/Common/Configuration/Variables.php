<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Common\Configuration;

/**
 * Environment variables defined by the Solarwinds for the PHP SDK.
 * @see
 */
interface Variables
{
    /**
     * General SDK Configuration
     */
    public const SW_APM_SERVICE_KEY = 'SW_APM_SERVICE_KEY';
    public const SW_APM_COLLECTOR = 'SW_APM_COLLECTOR';
    public const SW_APM_SETTINGS_JSON_PATH = 'SW_APM_SETTINGS_JSON_PATH';
    /**
     * k8s resource attributes configuration
     */
    public const SW_K8S_POD_NAMESPACE = 'SW_K8S_POD_NAMESPACE';
    public const SW_K8S_POD_UID = 'SW_K8S_POD_UID';
    public const SW_K8S_POD_NAME = 'SW_K8S_POD_NAME';

}
