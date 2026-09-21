<?php

declare(strict_types=1);
/**
 * Nag configuration class
 *
 * Provides access to the Nag configuration settings.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail'];
 * New pattern: $config = $injector->get(NagConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Nag;

use Horde\Core\Config\State;
use Horde\Injector\Attribute\Factory;

#[Factory(factory: NagConfigFactory::class, method: 'create')]
class NagConfig extends State {}
