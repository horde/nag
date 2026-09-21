<?php

declare(strict_types=1);
/**
 * Nag configuration class factory
 *
 * Creates instances of the NagConfig class.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail'];
 * New pattern: $config = $injector->get(NagConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Nag;

use Horde\Core\Config\ConfigLoader;
use Horde\Injector\Injector;

class NagConfigFactory
{
    public function __construct(private Injector $injector) {}

    public function create(): NagConfig
    {
        $state = $this->injector->get(ConfigLoader::class)->load('nag');
        return new NagConfig($state->toArray());
    }
}
