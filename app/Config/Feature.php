<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Enable/disable backward compatibility breaking features.
 */
class Feature extends BaseConfig
{
    /**
     * Use improved new auto routing instead of the default legacy version.
     */
    public bool $autoRoutesImproved = true;

    /**
     * Use the old Filter Execution Order.
     */
    public bool $oldFilterOrder = false;

    /**
     * If true, limit(0) will be treated as "no limit".
     */
    public bool $limitZeroAsAll = true;

    /**
     * If true, strict locale negotiation is enabled.
     */
    public bool $strictLocaleNegotiation = false;
}
