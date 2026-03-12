<?php
/**
 * Setup default routes
 */
namespace Horde\Nag;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

$mapper->connect('/t/complete',
    array(
        'controller' => 'CompleteTask',
    ));

$mapper->connect('/t/save',
    array(
        'controller' => 'SaveTask',
    ));

// Responsive routes
$mapper->connect(
    'ResponsiveTasks',
    'responsive',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

// Responsive filter routes
$mapper->connect(
    'ResponsiveTasksAll',
    'responsive/all',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

$mapper->connect(
    'ResponsiveTasksIncomplete',
    'responsive/incomplete',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

$mapper->connect(
    'ResponsiveTasksComplete',
    'responsive/complete',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

$mapper->connect(
    'ResponsiveTasksFuture',
    'responsive/future',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

$mapper->connect(
    'ResponsiveTasksFutureIncomplete',
    'responsive/future-incomplete',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

$mapper->connect(
    'ResponsiveTaskAdd',
    'responsive/add',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

$mapper->connect(
    'ResponsiveTask',
    'responsive/task/:tasklist/:id',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);

$mapper->connect(
    'ResponsiveTaskEdit',
    'responsive/edit/:tasklist/:id',
    [
        'controller' => Responsive\ResponsiveController::class,
        'HordeAuthType' => 'authenticate',
        'stack' => [],
    ]
);
