<?php
/**
 * See horde/config/prefs.php for documentation on the structure of this file.
 *
 * IMPORTANT: Local overrides MUST be placed in pref.local.php, or
 * prefs-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$prefGroups['display'] = array(
    'column' => _("General Preferences"),
    'label' => _("Display Preferences"),
    'desc' => _("Change your task sorting and display preferences."),
    'members' => array('tasklist_columns', 'sortby', 'altsortby', 'sortdir'),
);

$prefGroups['deletion'] = array(
    'column' => _("General Preferences"),
    'label' => _("Delete Confirmation"),
    'desc' => _("Delete button behaviour"),
    'members' => array('delete_opt'),
);

$prefGroups['tasks'] = array(
    'column' => _("General Preferences"),
    'label' => _("Task Defaults"),
    'desc' => _("Defaults for new tasks"),
    'members' => array('default_due', 'default_due_days', 'default_due_time'),
);

$prefGroups['share'] = array(
    'column' => _("Task List and Share Preferences"),
    'label' => _("Default Task List"),
    'desc' => _("Choose your default task list."),
    'members' => array('default_tasklist'),
);

$prefGroups['notification'] = array(
    'column' => _("Task List and Share Preferences"),
    'label' => _("Notifications"),
    'desc' => _("Choose if you want to be notified of task changes and task alarms."),
    'members' => array('task_notification', 'task_notification_exclude_self', 'task_alarms_select'),
);

$prefGroups['external'] = array(
    'column'  => _("Task List and Share Preferences"),
    'label'   => _("External Data"),
    'desc'    => _("Show data from other applications or sources."),
    'members' => array('show_external'),
);

// columns in the list view
$_prefs['tasklist_columns'] = array(
    'value' => 'a:3:{i:0;s:8:"priority";i:1;s:3:"due";i:2;s:8:"category";}',
    'type' => 'multienum',
    'enum' => array(
        'tasklist' => _("Task List"),
        'priority' => _("Priority"),
        'assignee' => _("Assignee"),
        'due' => _("Due Date"),
        'start' => _("Start Date"),
        'estimate' => _("Estimated Time"),
        'category' => _("Category")
    ),
    'desc' => _("Select the columns that should be shown in the list view:")
);

// show the task list options panel?
// a value of 0 = no, 1 = yes
$_prefs['show_panel'] = array(
    'value' => 1
);

// user preferred sorting column
$_prefs['sortby'] = array(
    'value' => Nag::SORT_PRIORITY,
    'type' => 'enum',
    'enum' => array(
        Nag::SORT_PRIORITY => _("Priority"),
        Nag::SORT_NAME => _("Task Name"),
        Nag::SORT_CATEGORY => _("Category"),
        Nag::SORT_DUE => _("Due Date"),
        Nag::SORT_START => _("Start Date"),
        Nag::SORT_COMPLETION => _("Completed?"),
        Nag::SORT_ESTIMATE => _("Estimated Time"),
        Nag::SORT_ASSIGNEE => _("Assignee"),
        Nag::SORT_OWNER => _("Task List")
    ),
    'desc' => _("Sort tasks by:"),
);

// alternate sort column
$_prefs['altsortby'] = array(
    'value' => Nag::SORT_CATEGORY,
    'type' => 'enum',
    'enum' => array(
        Nag::SORT_PRIORITY => _("Priority"),
        Nag::SORT_NAME => _("Task Name"),
        Nag::SORT_CATEGORY => _("Category"),
        Nag::SORT_DUE => _("Due Date"),
        Nag::SORT_START => _("Start Date"),
        Nag::SORT_COMPLETION => _("Completed?"),
        Nag::SORT_ESTIMATE => _("Estimated Time"),
        Nag::SORT_ASSIGNEE => _("Assignee"),
        Nag::SORT_OWNER => _("Task List")
    ),
    'desc' => _("Then:"),
);

// user preferred sorting direction
$_prefs['sortdir'] = array(
    'value' => Nag::SORT_ASCEND,
    'type' => 'enum',
    'enum' => array(
        Nag::SORT_ASCEND => _("Ascending"),
        Nag::SORT_DESCEND => _("Descending")
    ),
    'desc' => _("Sort direction:"),
);

// preference for delete confirmation dialog.
$_prefs['delete_opt'] = array(
    'value' => 1,
    'type' => 'checkbox',
    'desc' => _("Do you want to confirm deleting entries?"),
);

// default to tasks having a due date?
$_prefs['default_due'] = array(
    'value' => 0,
    'type' => 'checkbox',
    'desc' => _("When creating a new task, should it default to having a due date?"),
);

// default number of days out for due dates
$_prefs['default_due_days'] = array(
    'value' => 1,
    'type' => 'number',
    'desc' => _("When creating a new task, how many days in the future should the default due date be (0 means today)?"),
);

// default due time
$_prefs['default_due_time'] = array(
    'value' => 'now',
    'type' => 'enum',
    'desc' => _("What do you want to be the default due time for tasks?")
);

// new task notifications
$_prefs['task_notification'] = array(
    'value' => '',
    'type' => 'enum',
    'enum' => array(
        '' => _("No"),
        'owner' => _("On my task lists only"),
        'show' => _("On all shown task lists"),
        'read' => _("On all task lists I have read access to")
    ),
    'desc' => _("Choose if you want to be notified of new, edited, and deleted tasks by email:"),
);

$_prefs['task_notification_exclude_self'] = array(
    'value' => 0,
    'locked' => false,
    'type' => 'checkbox',
    'desc' => _("Don't send me a notification if I've added, changed or deleted the task?")
);

// alarm methods
$_prefs['task_alarms_select'] = array(
    'type' => 'special'
);

$_prefs['task_alarms'] = array(
    'value' => 'a:1:{s:6:"notify";a:0:{}}'
);

// show data from other applications that can be listed as tasks?
$_prefs['show_external'] = array(
    'value' => 'a:0:{}',
    'type' => 'multienum',
    'desc' => _("Show data from any of these other applications in your task list?"),
);

// show complete/incomplete tasks?
$_prefs['show_completed'] = array(
    'value' => 1,
    'type' => 'enum',
    'enum' => array(
        Nag::VIEW_ALL => _("All tasks"),
        Nag::VIEW_INCOMPLETE => _("Incomplete tasks"),
        Nag::VIEW_COMPLETE => _("Complete tasks"),
        Nag::VIEW_FUTURE => _("Future tasks")
    ),
    'desc' => _("Show complete, incomplete, or all tasks in the task list?"),
);

// user task categories
$_prefs['task_categories'] = array(
    'value' => ''
);

// default tasklists
// Set locked to true if you don't want users to have multiple task lists.
$_prefs['default_tasklist'] = array(
    'value' => $GLOBALS['registry']->getAuth() ? $GLOBALS['registry']->getAuth() : 0,
    'type' => 'enum',
    'desc' => _("Your default task list:")
);

// store the task lists to diplay
$_prefs['display_tasklists'] = array(
    'value' => 'a:0:{}'
);
