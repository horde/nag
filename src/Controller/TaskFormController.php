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
use Horde_Core_Perms;
use Horde_Core_Script_Package_Keynavlist;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
use Horde_Registry;
use Horde_Share_Exception;
use Horde_Variables;
use Horde\Util\Util;
use Nag;
use Nag_Exception;
use Nag_Factory_Driver;
use Nag_Form_Task;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 controller for displaying the task form (add, edit, re-display).
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class TaskFormController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Horde_Registry $registry,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Core_Perms $perms,
        private readonly Nag_Factory_Driver $driverFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        global $nag_shares;

        $params = array_merge(
            $request->getQueryParams(),
            (array) $request->getParsedBody()
        );

        $actionID = $params['actionID'] ?? null;

        if ($actionID === null) {
            return $this->redirect((string) Horde::url('list.php', true));
        }

        return match ($actionID) {
            'add_task' => $this->addTask($params),
            'modify_task' => $this->modifyTask($params, $nag_shares),
            'delete_task' => $this->deleteTask($params, $nag_shares),
            'task_form' => $this->redisplayForm(),
            default => $this->redirect((string) Horde::url('list.php', true)),
        };
    }

    /**
     * Render a task form with full page chrome.
     *
     * Called directly by SaveTaskController on validation failure.
     */
    public function renderTaskForm(Nag_Form_Task $form): ResponseInterface
    {
        $datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
        if (!file_exists($this->registry->get('jsfs', 'horde') . '/date/' . $datejs)) {
            $datejs = 'en-US.js';
        }

        Horde::startBuffer();
        $form->renderActive();
        $formHtml = Horde::endBuffer();

        $html = $this->renderChrome($form->getTitle(), function () use ($datejs, $formHtml) {
            $this->pageOutput->addScriptFile('date/' . $datejs, 'horde');
            $this->pageOutput->addScriptFile('date/date.js', 'horde');
            $this->pageOutput->addScriptFile('task.js');
            $this->pageOutput->addScriptPackage(Horde_Core_Script_Package_Keynavlist::class);
            require NAG_TEMPLATES . '/javascript_defs.php';
            Nag::status();
            echo $formHtml;
        });

        return $this->htmlResponse($html);
    }

    private function addTask(array $params): ResponseInterface
    {
        if ($this->perms->hasAppPermission('max_tasks') !== true
            && $this->perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
            Horde::permissionDeniedError(
                'nag',
                'max_tasks',
                sprintf(
                    _("You are not allowed to create more than %d tasks."),
                    $this->perms->hasAppPermission('max_tasks')
                )
            );
            return $this->redirect((string) Horde::url('list.php', true));
        }

        $vars = Horde_Variables::getDefaultVariables();
        if (!$vars->exists('tasklist_id')) {
            $vars->set('tasklist_id', Nag::getDefaultTasklist(Horde_Perms::EDIT));
        }
        if (!empty($params['parent_task'])) {
            $vars->set('parent', $params['parent_task']);
        }

        $form = new Nag_Form_Task($vars, _("New Task"));

        return $this->renderTaskForm($form);
    }

    private function modifyTask(array $params, $nag_shares): ResponseInterface
    {
        $taskId = $params['task'] ?? null;
        $tasklistId = $params['tasklist'] ?? null;

        if (!$taskId || !$tasklistId) {
            return $this->redirect((string) Horde::url('list.php', true));
        }

        try {
            $share = $nag_shares->getShare($tasklistId);
        } catch (Horde_Share_Exception $e) {
            $this->notification->push(
                sprintf(_("Access denied editing task: %s"), $e->getMessage()),
                'horde.error'
            );
            return $this->redirect((string) Horde::url('list.php', true));
        }

        if (!$share->hasPermission($this->registry->getAuth(), Horde_Perms::EDIT)) {
            $this->notification->push(_("Access denied editing task."), 'horde.error');
            return $this->redirect((string) Horde::url('list.php', true));
        }

        try {
            $task = Nag::getTask($tasklistId, $taskId);
        } catch (Nag_Exception $e) {
            $this->notification->push(_("Task not found."), 'horde.error');
            return $this->redirect((string) Horde::url('list.php', true));
        }

        if (!isset($task) || !isset($task->id)) {
            $this->notification->push(_("Task not found."), 'horde.error');
            return $this->redirect((string) Horde::url('list.php', true));
        }

        if ($task->private && $task->owner != $this->registry->getAuth()) {
            $this->notification->push(_("Access denied editing task."), 'horde.error');
            return $this->redirect((string) Horde::url('list.php', true));
        }

        $h = $task->toHash();
        $h['tags'] = implode(',', $h['tags']);
        $vars = new Horde_Variables($h);
        $vars->set('old_tasklist', $task->tasklist);
        $vars->set('url', $params['url'] ?? null);
        if (!empty($params['list'])) {
            $vars->set('list', $params['list']);
        }
        if (!empty($params['tab_name'])) {
            $vars->set('tab_name', $params['tab_name']);
        }

        $form = new Nag_Form_Task($vars, sprintf(_("Edit: %s"), $task->name));
        if (!$task->completed) {
            $task->loadChildren();
            $form->setTask($task);
        }

        return $this->renderTaskForm($form);
    }

    private function deleteTask(array $params, $nag_shares): ResponseInterface
    {
        $taskId = $params['task'] ?? null;
        $tasklistId = $params['tasklist'] ?? null;

        if (!empty($taskId)) {
            try {
                $task = Nag::getTask($tasklistId, $taskId);
                $task->loadChildren();
                $share = $nag_shares->getShare($tasklistId);

                if (!$share->hasPermission($this->registry->getAuth(), Horde_Perms::DELETE)) {
                    $this->notification->push(_("Access denied deleting task."), 'horde.error');
                } else {
                    $storage = $this->driverFactory->create($tasklistId);
                    try {
                        $storage->delete($taskId);
                    } catch (Nag_Exception $e) {
                        $this->notification->push(
                            sprintf(_("There was a problem deleting %s: %s"), $task->name, $e->getMessage()),
                            'horde.error'
                        );
                    }
                    $this->notification->push(
                        sprintf(_("Deleted %s."), $task->name),
                        'horde.success'
                    );
                }
            } catch (Horde_Share_Exception $e) {
                $this->notification->push(
                    sprintf(_("Error deleting task: %s"), $e->getMessage()),
                    'horde.error'
                );
            } catch (Nag_Exception $e) {
                $this->notification->push(
                    sprintf(_("Error deleting task: %s"), $e->getMessage()),
                    'horde.error'
                );
            }
        }

        $url = Horde::verifySignedUrl($params['url'] ?? '');
        if ($url) {
            return $this->redirect($url);
        }

        return $this->redirect((string) Horde::url('list.php', true));
    }

    private function redisplayForm(): ResponseInterface
    {
        $vars = Horde_Variables::getDefaultVariables();
        $form = new Nag_Form_Task(
            $vars,
            $vars->get('task_id')
                ? sprintf(_("Edit: %s"), $vars->get('name'))
                : _("New Task")
        );

        return $this->renderTaskForm($form);
    }
}
