<?php

declare(strict_types=1);

/**
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */

namespace Horde\Nag\Controller;

use Horde;
use Horde\Core\Controller\Traits\JsonResponseTrait;
use Horde\Core\Controller\Traits\RedirectResponseTrait;
use Horde_Notification_Handler;
use Nag_CompleteTask;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 controller for toggling task completion status.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class CompleteTaskController implements RequestHandlerInterface
{
    use JsonResponseTrait;
    use RedirectResponseTrait;

    public function __construct(
        private readonly Horde_Notification_Handler $notification,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = array_merge(
            $request->getQueryParams(),
            (array) $request->getParsedBody()
        );

        if (isset($params['task']) && isset($params['tasklist'])) {
            $nag_task = new Nag_CompleteTask();
            $result = $nag_task->result($params['task'], $params['tasklist']);
        } else {
            $result = ['error' => 'missing parameters'];
        }

        if (($params['format'] ?? '') === 'json') {
            return $this->jsonResponse($result);
        }

        $url = Horde::verifySignedUrl($params['url'] ?? '');
        if ($url) {
            return $this->redirect($url);
        }

        return $this->redirect((string) Horde::url('list.php', true));
    }
}
