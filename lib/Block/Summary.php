<?php

/**
 */
class Nag_Block_Summary extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = [])
    {
        parent::__construct($app, $params);

        $this->_name = _("Tasks Summary");
    }

    /**
     */
    protected function _title()
    {
        global $registry;

        $label = !empty($this->_params['block_title'])
            ? $this->_params['block_title']
            : $registry->get('name');

        return Horde::url($registry->getInitialPage(), true)->link()
            . htmlspecialchars($label) . '</a>';
    }

    /**
     */
    protected function _params()
    {
        $tasklists = [];
        foreach (Nag::listTasklists() as $id => $tasklist) {
            $tasklists[$id] = Nag::getLabel($tasklist);
        }

        return [
            'block_title' => [
                'type' => 'text',
                'name' => _("Block title"),
                'default' => $GLOBALS['registry']->get('name'),
            ],
            'show_pri' => [
                'type' => 'checkbox',
                'name' => _("Show priorities?"),
                'default' => 1,
            ],
            'show_actions' => [
                'type' => 'checkbox',
                'name' => _("Show action buttons?"),
                'default' => 1,
            ],
            'show_due' => [
                'type' => 'checkbox',
                'name' => _("Show due dates?"),
                'default' => 1,
            ],
            'show_tasklist' => [
                'type' => 'checkbox',
                'name' => _("Show task list name?"),
                'default' => 1,
            ],
            'show_alarms' => [
                'type' => 'checkbox',
                'name' => _("Show task alarms?"),
                'default' => 1,
            ],
            'show_overdue' => [
                'type' => 'checkbox',
                'name' => _("Always show overdue tasks?"),
                'default' => 1,
            ],
            'show_completed' => [
                'type' => 'checkbox',
                'name' => _("Always show completed and future tasks?"),
                'default' => 1,
            ],
            'show_tasklists' => [
                'type' => 'multienum',
                'name' => _("Show tasks from these task lists"),
                'default' => [],
                'values' => $tasklists,
            ],
        ];
    }

    /**
     */
    protected function _content()
    {
        global $conf, $prefs, $registry;

        $html = '';

        if (!empty($this->_params['show_alarms'])) {
            $messages = [];
            try {
                $alarmList = Nag::listAlarms($_SERVER['REQUEST_TIME']);
            } catch (Nag_Exception $e) {
                return '<em>' . htmlspecialchars($e->getMessage())
                    . '</em>';
            }
            foreach ($alarmList as $task) {
                $differential = $task->getNextDue()->timestamp() - $_SERVER['REQUEST_TIME'];
                $key = $differential;
                while (isset($messages[$key])) {
                    $key++;
                }
                $viewurl = Horde::url('view.php', true)->add([
                    'task' => $task->id,
                    'tasklist' => $task->tasklist,
                ]);
                $link = $viewurl->link() .
                    (!empty($task->name) ? htmlspecialchars($task->name) : _("[none]")) .
                    '</a>';
                if ($differential >= -60 && $differential < 60) {
                    $messages[$key] = sprintf(_("%s is due now."), $link);
                } elseif ($differential >= 60) {
                    $messages[$key] = sprintf(
                        _("%s is due in %s"),
                        $link,
                        Nag::secondsToString($differential)
                    );
                }
            }

            ksort($messages);
            foreach ($messages as $message) {
                $html .= '<tr><td class="control">'
                    . Horde::img('alarm_small.png') . '&nbsp;&nbsp;<strong>'
                    . $message . '</strong></td></tr>';
            }

            if (!empty($messages)) {
                $html .= '</table><br /><table cellspacing="0" width="100%" class="linedRow">';
            }
        }

        $i = 0;
        try {
            $tasks = Nag::listTasks(
                [
                    'tasklists' => $this->_params['show_tasklists']
                        ?? array_keys(Nag::listTasklists(false, Horde_Perms::READ)),
                    'completed' => empty($this->_params['show_completed'])
                        ? Nag::VIEW_INCOMPLETE
                        : Nag::VIEW_ALL,
                    'include_history' => false]
            );
        } catch (Nag_Exception $e) {
            return '<em>' . htmlspecialchars($e->getMessage()) . '</em>';
        }

        $tasks->reset();
        while ($task = $tasks->each()) {
            $due = $task->due ? $task->getNextDue() : null;

            // Only print tasks due in the past if the show_overdue flag is on.
            if ($due && $due->before($_SERVER['REQUEST_TIME']) &&
                empty($this->_params['show_overdue'])) {
                continue;
            }

            if ($task->completed) {
                $class = 'closed';
            } elseif ($due && $due->before($_SERVER['REQUEST_TIME'])) {
                $class = 'overdue';
            } else {
                $class = '';
            }
            $style = ' style="background-color:' . $task->backgroundColor()
                . ';color:' . $task->foregroundColor() . '"';

            $html .= '<tr class="' . $class . '">';

            if (!empty($this->_params['show_actions'])) {
                $taskurl = Horde::url('task.php', true)->add([
                    'task' => $task->id,
                    'tasklist' => $task->tasklist,
                    'url' => Horde::signUrl(Horde::selfUrl(true)),
                ]);
                $label = sprintf(_("Edit \"%s\""), $task->name);
                $html .= '<td width="1%"' . $style . '>'
                    . $taskurl->copy()->add('actionID', 'modify_task')->link()
                    . Horde::img('edit-sidebar-' . substr($task->foregroundColor(), 1) . '.png', $label)
                    . '</a></td>';
                if ($task->completed) {
                    $html .= '<td width="1%"' . $style . '>'
                        . Horde::img('checked.png', _("Completed")) . '</td>';
                } else {
                    $label = sprintf(_("Complete \"%s\""), $task->name);
                    $html .= '<td width="1%"' . $style . '>'
                        . Horde::url(
                            $conf['urls']['pretty'] == 'rewrite'
                                ? 't/complete'
                                : 'task/complete.php'
                        )->add([
                            'task' => $task->id,
                            'tasklist' => $task->tasklist,
                            'url' => Horde::selfUrl(true),
                        ])->link()
                        . Horde::img('unchecked.png', $label) . '</a></td>';
                }
            }

            if (!empty($this->_params['show_pri'])) {
                $html .= '<td align="center"' . $style . '>&nbsp;'
                    . Nag::formatPriority($task->priority) . '&nbsp;</td>';
            }

            if (!empty($this->_params['show_tasklist'])) {
                $html .= '<td width="1%" class="nowrap"' . $style . '>'
                    . htmlspecialchars(Nag::getLabel($GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create()->getShare($task->tasklist)))
                    . '&nbsp;</td>';
            }

            $html .= '<td' . $style . '>';

            $viewurl = Horde::url('view.php', true)->add([
                'task' => $task->id,
                'tasklist' => $task->tasklist,
            ]);
            $html .= $task->treeIcons()
                . $viewurl->link(['title' => $task->desc, 'style' => 'color:' . $task->foregroundColor()])
                . (!empty($task->name)
                   ? htmlspecialchars($task->name) : _("[none]"))
                . '</a>';

            if ($due && empty($task->completed) &&
                !empty($this->_params['show_due'])) {
                $html .= ' ('
                    . $due->strftime($prefs->getValue('date_format'))
                    . ')';
            }

            $html .= '</td>';
            $html .= "</tr>\n";
        }

        if (empty($html)) {
            return '<em>' . _("No tasks to display") . '</em>';
        }

        return '<table cellspacing="0" width="100%" class="linedRow">'
            . $html . '</table>';
    }

}
