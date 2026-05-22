<?php

/**
 * Legacy entry point for task form display.
 *
 * Delegates to TaskFormController. The PSR-15 route /t/task handles this
 * natively; this file exists for backward compatibility with direct access.
 *
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jon Parise <jon@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

use Horde\Http\Server\RequestBuilder;
use Horde\Nag\Controller\TaskFormController;

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

$injector = $GLOBALS['injector'];

$controller = new TaskFormController(
    $injector->getInstance('Horde_Registry'),
    $injector->getInstance('Horde_Notification_Handler'),
    $injector->getInstance('Horde_PageOutput'),
    $injector->getInstance('Horde_Core_Perms'),
    $injector->getInstance('Nag_Factory_Driver'),
);

$request = (new RequestBuilder())->withGlobalVariables()->build();
$response = $controller->handle($request);

http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header($name . ': ' . $value, false);
    }
}
echo (string) $response->getBody();
