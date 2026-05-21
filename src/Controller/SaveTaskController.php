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
use Horde\Core\Controller\Traits\RedirectResponseTrait;
use Horde_Core_Perms;
use Horde_Notification_Handler;
use Horde_Perms;
use Horde_Registry;
use Horde_Share_Exception;
use Horde_Util;
use Horde_Variables;
use Nag;
use Nag_Exception;
use Nag_Factory_Driver;
use Nag_Form_Task;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Horde\Util\Util;

/**
 * PSR-15 controller for saving (create/update/delete) a task.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class SaveTaskController implements RequestHandlerInterface
{
    use RedirectResponseTrait;

    public function __construct(
        private readonly Horde_Registry $registry,
        private readonly Horde_Notification_Handler $notification,
        private readonly Nag_Factory_Driver $driverFactory,
        private readonly Horde_Core_Perms $perms,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        global $nag_shares, $prefs, $conf;

        $vars = Horde_Variables::getDefaultVariables();

        $form = new Nag_Form_Task(
            $vars,
            $vars->get('task_id')
                ? sprintf(_("Edit: %s"), $vars->get('name'))
                : _("New Task")
        );

        if (!$form->validate($vars)) {
            $_REQUEST['actionID'] = 'task_form';
            // Legacy task.php expects Horde globals (e.g. on task list reload).
            $GLOBALS['registry'] = $this->registry;
            require NAG_BASE . '/task.php';
            exit;
        }

        $info = $form->getInfo($vars);

        if ($vars->search_return) {
            return $this->redirect(
                (string) Horde::url('list.php', true)->add([
                    'actionID' => 'search_return',
                    'list' => $vars->list,
                    'tab_name' => $vars->tab_name,
                ])
            );
        }

        if ($vars->deletebutton) {
            return $this->deleteTask($info);
        }

        if ($prefs->isLocked('default_tasklist')
            || count($this->getTasklists()) <= 1) {
            $info['tasklist_id'] = $info['old_tasklist'] = Nag::getDefaultTasklist(Horde_Perms::EDIT);
        }

        try {
            $share = $nag_shares->getShare($info['tasklist_id']);
        } catch (Horde_Share_Exception $e) {
            $this->notification->push(
                sprintf(_("Access denied saving task: %s"), $e->getMessage()),
                'horde.error'
            );
            return $this->redirect((string) Horde::url('list.php', true));
        }

        if (!$share->hasPermission($this->registry->getAuth(), Horde_Perms::EDIT)) {
            $this->notification->push(
                _("Access denied saving task to this task list."),
                'horde.error'
            );
            return $this->redirect((string) Horde::url('list.php', true));
        }

        if (!empty($info['task_id']) && !empty($info['old_tasklist'])) {
            $storage = $this->driverFactory->create($info['old_tasklist']);
            $info['tasklist'] = $info['tasklist_id'];
            try {
                $storage->modify($info['task_id'], $info);
            } catch (Nag_Exception $e) {
                $this->notification->push(
                    sprintf(_("There was a problem saving the task: %s."), $e->getMessage()),
                    'horde.error'
                );
                return $this->redirect((string) Horde::url('list.php', true));
            }
            $method = Nag::ITIP_UPDATE;
            $newid = [$info['task_id']];
        } else {
            if ($this->perms->hasAppPermission('max_tasks') !== true
                && $this->perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
                return $this->redirect((string) Horde::url('list.php', true));
            }

            $storage = $this->driverFactory->create($info['tasklist_id']);
            unset($info['owner']);
            unset($info['uid']);
            try {
                $newid = $storage->add($info);
            } catch (Nag_Exception $e) {
                $this->notification->push(
                    sprintf(_("There was a problem saving the task: %s."), $e->getMessage()),
                    'horde.error'
                );
                return $this->redirect((string) Horde::url('list.php', true));
            }
            $method = Nag::ITIP_REQUEST;
        }

        if ($conf['assignees']['allow_external']) {
            Nag::sendITipNotifications($storage->get($newid[0]), $this->notification, $method);
        }

        $this->notification->push(sprintf(_("Saved %s."), $info['name']), 'horde.success');

        if ($vars->savenewbutton) {
            $url = Horde::url('task.php', true)->add([
                'actionID' => 'add_task',
                'tasklist_id' => $info['tasklist_id'],
                'parent' => $info['parent'],
            ]);
        } else {
            $url = Horde::verifySignedUrl(Util::getFormData('url'));
            if (!$url) {
                $url = Horde::url('list.php', true);
            } else {
                $url = Horde::url($url, true);
            }
        }

        return $this->redirect((string) $url);
    }

    private function deleteTask(array $info): ResponseInterface
    {
        global $nag_shares;

        try {
            $share = $nag_shares->getShare($info['old_tasklist']);
        } catch (Horde_Share_Exception $e) {
            $this->notification->push(
                sprintf(_("Access denied deleting task: %s"), $e->getMessage()),
                'horde.error'
            );
            return $this->redirect((string) Horde::url('list.php', true));
        }

        $task = Nag::getTask($info['old_tasklist'], $info['task_id']);
        $task->loadChildren();

        if (!$share->hasPermission($this->registry->getAuth(), Horde_Perms::DELETE)) {
            $this->notification->push(_("Access denied deleting task"), 'horde.error');
            return $this->redirect((string) Horde::url('list.php', true));
        }

        $storage = $this->driverFactory->create($info['old_tasklist']);
        try {
            $storage->delete($info['task_id']);
            $this->notification->push(_("Task successfully deleted"), 'horde.success');
        } catch (Nag_Exception $e) {
            $this->notification->push(
                sprintf(_("Error deleting task: %s"), $e->getMessage()),
                'horde.error'
            );
        }

        return $this->redirect((string) Horde::url('list.php', true));
    }

    /**
     * Return tasklists the current user has PERMS_EDIT on.
     *
     * @return array  A hash of tasklist objects.
     */
    private function getTasklists(): array
    {
        $tasklist_enums = [];
        $user = $this->registry->getAuth();
        foreach (Nag::listTasklists(false, Horde_Perms::SHOW, false) as $tl_id => $tl) {
            if (!$tl->hasPermission($user, Horde_Perms::EDIT)) {
                continue;
            }
            $tasklist_enums[$tl_id] = Nag::getLabel($tl);
        }

        return $tasklist_enums;
    }
}
