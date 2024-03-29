<?php
/**
 * Nag_Task handles as single task as well as a list of tasks and implements a
 * recursive iterator to handle a (hierarchical) list of tasks.
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 *
 * @property tags array  An array of tags this task is tagged with.
 */
class Nag_Task
{
    /**
     * The task id.
     *
     * @var string
     */
    public $id;

    /**
     * This task's tasklist id.
     *
     * @var string
     */
    public $tasklist;

    /**
     * This task's tasklist name.
     *
     * Overrides the $tasklist's share name.
     *
     * @var string
     */
    public $tasklist_name;

    /**
     * The task uid.
     *
     * @var string
     */
    public $uid;

    /**
     * The task owner.
     *
     * @var string
     */
    public $owner;

    /**
     * The task assignee.
     *
     * @var string
     */
    public $assignee;

    /**
     * The task title.
     *
     * @var string
     */
    public $name;

    /**
     * The task decription.
     *
     * @var string
     */
    public $desc;

    /**
     * The start date timestamp.
     *
     * @var integer
     */
    public $start;

    /**
     * The due date timestamp.
     *
     * @var integer
     */
    public $due;

    /**
     * Recurrence rules for recurring tasks.
     *
     * @var Horde_Date_Recurrence
     */
    public $recurrence;

    /**
     * The task priority from 1 = highest to 5 = lowest.
     *
     * @var integer
     */
    public $priority;

    /**
     * The estimated task length.
     *
     * @var float
     */
    public $estimate;

    /**
     * The actual task length.
     *
     * @var float
     */
    public $actual;

    /**
     * Whether the task is completed.
     *
     * @var boolean
     */
    public $completed;

    /**
     * The completion date timestamp.
     *
     * @var integer
     */
    public $completed_date;

    /**
     * The creation time.
     *
     * @var Horde_Date
     */
    public $created;

    /**
     * The creator string.
     *
     * @var string
     */
    public $createdby;

    /**
     * The last modification time.
     *
     * @var Horde_Date
     */
    public $modified;

    /**
     * The last-modifier string.
     *
     * @var string
     */
    public $modifiedby;

    /**
     * The task alarm threshold in minutes.
     *
     * @var integer
     */
    public $alarm;

    /**
     * The particular alarm methods overridden for this task.
     *
     * @var array
     */
    public $methods;

    /**
     * Snooze minutes for this event's alarm.
     *
     * @see Horde_Alarm::snooze()
     *
     * @var integer
     */
    public $snooze;

    /**
     * Whether the task is private.
     *
     * @var boolean
     */
    public $private;

    /**
     * URL to view the task.
     *
     * @var string
     */
    public $view_link;

    /**
     * URL to complete the task.
     *
     * @var string
     */
    public $complete_link;

    /**
     * URL to edit the task.
     *
     * @var string
     */
    public $edit_link;

    /**
     * URL to delete the task.
     *
     * @var string
     */
    public $delete_link;

    /**
     * The parent task's id.
     *
     * @var string
     */
    public $parent_id = '';

    /**
     * The parent task.
     *
     * @var Nag_Task
     */
    public $parent;

    /**
     * The sub-tasks.
     *
     * @var array
     */
    public $children = array();

    /**
     * This task's idention (child) level.
     *
     * @var integer
     */
    public $indent = 0;

    /**
     * Whether this is the last sub-task.
     *
     * @var boolean
     */
    public $lastChild;

    /**
     * A storage driver.
     *
     * @var Nag_Driver
     */
    protected $_storage;

    public array $ourCaldavAttributes = [
        'AALARM',
        'ALARM',
        'ATTENDEE',
        'CATEGORIES',
        'CLASS',
        'CREATED', // Assume Horde History is right about this. This may not always be true.
        'DCREATED',
        'DTSTART',
        'DESCRIPTION',
        'DUE',
        'EXDATE',
        'LAST-MODIFIED', // Let Horde History handle LAST-MODIFIED attribute
        'ORGANIZER',
        'PRIORITY',
        'RELATED-TO',
        'RRULE',
        'STATUS',
        'SUMMARY',
        'UID',
        'X-MOZ-LASTACK',
        'X-MOZ-SNOOZE-TIME',
        'X-HORDE-ESTIMATE',
        'X-HORDE-EFFORT'
    ];

    public array $otherCaldavAttributes = [];

    /**
     * Internal flag.
     *
     * @var boolean
     * @see each()
     */
    protected $_inlist = false;

    /**
     * Internal pointer.
     *
     * @var integer
     * @see each()
     */
    protected $_pointer = 0;

    /**
     * Task id => pointer dictionary.
     *
     * @var array
     */
    protected $_dict = array();

    /**
     * Task tags from the storage backend (e.g. Kolab)
     *
     * @var array
     */
    public $internaltags;

    /**
     * Task organizer
     *
     * @var string
     */
    public $organizer;

    /**
     * The assignment status of this task.
     *
     * @var integer
     */
    public $status;

    /**
     * Task tags (lazy loaded).
     *
     * @var string
     */
    protected $_tags;

    /**
     * Constructor.
     *
     * Takes a hash and returns a nice wrapper around it.
     *
     * @param Nag_Driver $storage  A storage driver.
     * @param array $task          A task hash.
     */
    public function __construct(Nag_Driver $storage = null, array $task = null)
    {
        if ($storage) {
            $this->_storage = $storage;
        }
        if ($task) {
            $this->merge($task);
        }
    }

    /**
     * Getter.
     *
     * Returns 'tags' property.
     *
     * @param string $name  Property name.
     *
     * @return mixed  Property value.
     */
    public function __get($name)
    {
        switch ($name) {
        case 'tags':
            if (!isset($this->_tags)) {
                $this->synchronizeTags($GLOBALS['injector']->getInstance('Nag_Tagger')->getTags($this->uid, 'task'));
            }
            return $this->_tags;
        }

        $trace = debug_backtrace();
        trigger_error('Undefined property via __get(): ' . $name
                      . ' in ' . $trace[0]['file']
                      . ' on line ' . $trace[0]['line'],
                      E_USER_NOTICE);
        return null;
    }

    /**
     * Setter.
     *
     * @param string $name  Property name.
     * @param mixed $value  Property value.
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'tags':
            $this->_tags = $value;
            return;
        }
        $trace = debug_backtrace();
        trigger_error('Undefined property via __set(): ' . $name
                      . ' in ' . $trace[0]['file']
                      . ' on line ' . $trace[0]['line'],
                      E_USER_NOTICE);
    }

    /**
     * Deep clone so we can clone the child objects too.
     *
     */
    public function __clone()
    {
        foreach ($this->children as $key => $value) {
            $this->children[$key] = clone $value;
        }
    }

    /**
     * Merges a task hash into this task object.
     *
     * @param array $task  A task hash.
     */
    public function merge(array $task)
    {
        foreach ($task as $key => $val) {
            switch ($key) {
            case 'tasklist_id':
                $key = 'tasklist';
                break;
            case 'task_id':
                $key = 'id';
                break;
            case 'parent':
                $key = 'parent_id';
                break;
            case 'other':
                $key = 'otherCaldavAttributes';
                $val = json_decode($val, true);
                break;
            }
            $this->$key = $val;
        }
    }

    /**
     * Disconnect this task from any child tasks. Used when building search
     * result sets since child tasks will be re-added if they actually match
     * the result, and there is no guarentee that a tasks's parent will
     * be present in the result set.
     */
    public function orphan()
    {
        $this->children = array();
        $this->_dict = array();
        $this->lastChild = null;
        $this->indent = null;
    }

    /**
     * Saves this task in the storage backend.
     *
     * @throws Nag_Exception
     */
    public function save()
    {
        $this->_storage->modify($this->id, $this->toHash(true));
    }

    /**
     * Returns the parent task of this task, if one exists.
     *
     * @return mixed  The parent task, null if none exists
     */
    public function getParent()
    {
        if (!$this->parent_id) {
            return null;
        }
        return Nag::getTask($this->tasklist, $this->parent_id);
    }

    /**
     * Adds a sub task to this task.
     *
     * @param Nag_Task $task  A sub task.
     */
    public function add(Nag_Task $task, $replace = false)
    {
        if (!isset($this->_dict[$task->id])) {
            $this->_dict[$task->id] = count($this->children);
            $task->parent = $this;
            $this->children[] = $task;
        } elseif ($replace) {
            $this->children[$this->_dict[$task->id]]= $task;
        }
    }

    /**
     * Loads all sub-tasks.
     *
     * @param
     */
    public function loadChildren($include_history = true)
    {
        try {
            $this->children = $this->_storage->getChildren($this->id, $include_history);
        } catch (Nag_Exception $e) {}
    }

    /**
     * Merges an array of tasks into this task's children.
     *
     * @param array $children  A list of Nag_Tasks.
     *
     */
    public function mergeChildren(array $children)
    {
        for ($i = 0, $c = count($children); $i < $c; ++$i) {
            $this->add($children[$i]);
        }
    }

    /**
     * Returns a sub task by its id.
     *
     * The methods goes recursively through all sub tasks until it finds the
     * searched task.
     *
     * @param string $key  A task id.
     *
     * @return Nag_Task  The searched task or null.
     */
    public function get($key)
    {
        return isset($this->_dict[$key]) ?
            $this->children[$this->_dict[$key]] :
            null;
    }

    /**
     * Returns whether this is a task (not a container) or contains any sub
     * tasks.
     *
     * @return boolean  True if this is a task or has sub tasks.
     */
    public function hasTasks()
    {
        return ($this->id) ? true : $this->hasSubTasks();
    }

    /**
     * Returns whether this task contains any sub tasks.
     *
     * @return boolean  True if this task has sub tasks.
     */
    public function hasSubTasks()
    {
        foreach ($this->children as $task) {
            if ($task->hasTasks()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns whether all sub tasks are completed.
     *
     * @return boolean  True if all sub tasks are completed.
     */
    public function childrenCompleted()
    {
        foreach ($this->children as $task) {
            if (!$task->completed || !$task->childrenCompleted()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns whether any tasks in the list are overdue.
     *
     * @return boolean  True if any task or sub tasks are overdue.
     */
    public function childrenOverdue()
    {
        if (!empty($this->due)) {
            $due = new Horde_Date($this->due);
            if ($due->compareDate(new Horde_Date(time())) <= 0) {
                return true;
            }
        }
        foreach ($this->children as $task) {
            if ($task->childrenOverdue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the number of tasks including this and any sub tasks.
     *
     * @return integer  The number of tasks and sub tasks.
     */
    public function count()
    {
        $count = $this->id ? 1 : 0;
        foreach ($this->children as $task) {
            $count += $task->count();
        }
        return $count;
    }

    /**
     * Returns the estimated length for this and any sub tasks.
     *
     * @return integer  The estimated length sum.
     */
    public function estimation()
    {
        $estimate = $this->estimate;
        foreach ($this->children as $task) {
            $estimate += $task->estimation();
        }
        return $estimate;
    }

    /**
     * Returns the actual length for this and any sub tasks.
     *
     * @return integer  The actual length sum.
     */
    public function actuals()
    {
        $actual = $this->actual;
        foreach ($this->children as $task) {
            $actual += $task->actuals();
        }
        return $actual;
    }

    /**
     * Returns whether this task is a recurring task.
     *
     * @return boolean  True if this is a recurring task.
     */
    public function recurs()
    {
        return isset($this->recurrence) &&
            !$this->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_NONE);
    }

    /**
     * Toggles completion status of this task. Moves a recurring task
     * to the next occurence on completion. Enforces the rule that sub
     * tasks must be completed before parent tasks.
     */
    public function toggleComplete($ignore_children = false)
    {
        if ($ignore_children) {
            $this->loadChildren();
            if (!$this->completed && !$this->childrenCompleted()) {
                throw new Nag_Exception(_("Must complete all children tasks."));
            }
        }
        if ($this->completed) {
            $this->completed_date = null;
            $this->completed = false;
            if ($parent = $this->getParent()) {
                if ($parent->completed) {
                    $parent->toggleComplete(true);
                    $parent->save();
                }
            }
            if ($this->recurs()) {
                /* Only delete the latest completion. */
                $completions = $this->recurrence->getCompletions();
                sort($completions);
                list($year, $month, $mday) = sscanf(
                    end($completions),
                    '%04d%02d%02d'
                );
                $this->recurrence->deleteCompletion($year, $month, $mday);
            }
            return;
        }

        if ($this->recurs()) {
            /* Get current occurrence (task due date) */
            $current = $this->recurrence->nextActiveRecurrence(new Horde_Date($this->due));
            if ($current) {
                $this->recurrence->addCompletion($current->year,
                                                 $current->month,
                                                 $current->mday);
                /* Advance this occurence by a day to indicate that we want the
                 * following occurence (Recurrence uses days as minimal time
                 * duration between occurrences). */
                $current->mday++;
                /* Only mark this due date completed if there is another
                 * occurence. */
                if ($next = $this->recurrence->nextActiveRecurrence($current)) {
                    $this->completed = false;
                    return;
                }
            }
        }

        $this->completed_date = time();
        $this->completed = true;
    }

    /**
     * Returns the next start date of this task.
     *
     * Takes recurring tasks into account.
     *
     * @return Horde_Date  The next start date.
     */
    public function getNextStart()
    {
        if (!$this->start) {
            return null;
        }

        if (!$this->recurs() ||
            !($completions = $this->recurrence->getCompletions())) {
            return new Horde_Date($this->start);
        }

        sort($completions);
        list($year, $month, $mday) = sscanf(
            end($completions),
            '%04d%02d%02d'
        );
        $lastCompletion = new Horde_Date($year, $month, $mday);
        $recurrence = clone $this->recurrence;
        $recurrence->start = new Horde_Date($this->start);

        return $recurrence->nextRecurrence($lastCompletion);
    }

    /**
     * Returns the next due date of this task.
     *
     * Takes recurring tasks into account.
     *
     * @return Horde_Date|null  The next due date or null if no due date.
     */
    public function getNextDue()
    {
        if (!$this->due) {
            return null;
        }
        if (!$this->recurs()) {
            return new Horde_Date($this->due);
        }
        if (!($nextActive = $this->recurrence->nextActiveRecurrence($this->due))) {
            return null;
        }
        return $nextActive;
    }

    /**
     * Format the description - link URLs, etc.
     *
     * @return string
     */
    public function getFormattedDescription()
    {
        $desc = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_TextFilter')
            ->filter($this->desc,
                     'text2html',
                     array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        try {
            return Horde::callHook('format_description', array($desc), 'nag');
        } catch (Horde_Exception_HookNotSet $e) {
            return $desc;
        }
    }

    /**
     * Resets the tasks iterator.
     *
     * Call this each time before looping through the tasks.
     *
     * @see each()
     */
    public function reset()
    {
        foreach (array_keys($this->children) as $key) {
            $this->children[$key]->reset();
        }
        $this->_pointer = 0;
        $this->_inlist = false;
    }

    /**
     * Return the task, if present anywhere in this tasklist, regardless of
     * child depth.
     *
     * @param  string $taskId  The task id we are looking for.
     *
     * @return Nag_Task|false  The task object, if found. Otherwise false.
     */
    public function hasTask($taskId)
    {
        $this->reset();
        while ($task = $this->each()) {
            if ($task->id == $taskId) {
                return $task;
            }
        }

        return false;
    }

    /**
     * Returns the next task iterating through all tasks and sub tasks.
     *
     * Call reset() each time before looping through the tasks:
     * <code>
     * $tasks->reset();
     * while ($task = $tasks->each() {
     *     ...
     * }
     *
     * @see reset()
     */
    public function each()
    {
        if ($this->id && !$this->_inlist) {
            $this->_inlist = true;
            return $this;
        }
        if ($this->_pointer >= count($this->children)) {
            return false;
        }
        $next = $this->children[$this->_pointer]->each();
        if ($next) {
            return $next;
        }
        $this->_pointer++;
        return $this->each();
    }

    /**
     * Helper method for getting only a slice of the total tasks in this list.
     *
     * @param integer $page     The starting page.
     * @param integer $perpage  The count of tasks per page.
     *
     * @return Nag_Task  The resulting task list.
     */
    public function getSlice($page = 0, $perpage = null)
    {
        $this->reset();

        // Position at start task
        $start = $page * (empty($perpage) ? 0 : $perpage);
        $count = 0;
        while ($count < $start) {
            if (!$this->each()) {
                return new Nag_Task();
            }
            ++$count;
        }

        $count = 0;
        $results = new Nag_Task();
        $max = (empty($perpage) ? ($this->count() - $start) : $perpage);
        while ($count < $max) {
            if ($next = $this->each()) {
                $results->add($next);
                ++$count;
            } else {
                $count = $max;
            }
        }
        $results->process();
        return $results;
    }

    /**
     * Processes a list of tasks by adding action links, obscuring details of
     * private tasks and calculating indentation.
     *
     * @param integer $indent  The indention level of the tasks.
     */
    public function process($indent = null)
    {
        global $conf;

        /* Link cache. */
        static $view_url_list, $task_url_list;

        /* Set indention. */
        if (is_null($indent)) {
            $indent = 0;
            if ($parent = $this->getParent()) {
                $indent = $parent->indent + 1;
            }
        }
        $this->indent = $indent;
        if ($this->id) {
            $indent++;
        }

        /* Process children. */
        for ($i = 0, $c = count($this->children); $i < $c; ++$i) {
            $this->children[$i]->process($indent);
        }

        /* Mark last child. */
        if (count($this->children)) {
            $this->children[count($this->children) - 1]->lastChild = true;
        }

        /* Only process further if this is really a (parent) task, not only a
         * task list container. */
        if (!$this->id) {
            return;
        }

        if (!isset($view_url_list[$this->tasklist])) {
            $view_url_list[$this->tasklist] = Horde::url('view.php')->add('tasklist', $this->tasklist);
            $task_url_list[$this->tasklist] = Horde::url('task.php')->add('tasklist', $this->tasklist);
        }

        /* Obscure private tasks. */
        if ($this->private && $this->owner != $GLOBALS['registry']->getAuth()) {
            $this->name = _("Private Task");
            $this->desc = '';
        }

        /* Create task links. */
        $this->view_link = $view_url_list[$this->tasklist]->copy()->add('task', $this->id);

        $task_url_task = $task_url_list[$this->tasklist]->copy()->add('task', $this->id);
        $this->complete_link = Horde::url(
            $conf['urls']['pretty'] == 'rewrite'
                ? 't/complete'
                : 'task/complete.php'
            )->add(array(
                'url' => Horde::signUrl(Horde::url('list.php')),
                'task' => $this->id,
                'tasklist' => $this->tasklist
            ));
        $this->edit_link = $task_url_task->copy()->add('actionID', 'modify_task');
        $this->delete_link = $task_url_task->copy()->add('actionID', 'delete_task');
    }

    /**
     * Returns the background color.
     *
     * @return string  A HTML color code.
     */
    public function backgroundColor()
    {
        try {
            $share = $GLOBALS['nag_shares']->getShare($this->tasklist);
            return $share->get('color') ?: '#ddd';
        } catch (Horde_Exception_NotFound $e) {
        }
        return '#ddd';
    }

    /**
     * Returns the foreground color.
     *
     * @return string  A HTML color code.
     */
    public function foregroundColor()
    {
        return Horde_Image::brightness($this->backgroundColor()) < 128
            ? '#fff'
            : '#000';
    }

    /**
     * Returns the HTML code for any tree icons, when displaying this task in
     * a tree view.
     *
     * @return string  The HTML code for necessary tree icons.
     */
    public function treeIcons()
    {
        $foreground = $this->foregroundColor() == '#fff' ? '-fff' : '';
        $html = '';

        $parent = $this->parent;
        for ($i = 1; $i < $this->indent; ++$i) {
            if ($parent && $parent->lastChild) {
                $html = Horde::img('tree/blank' . $foreground . '.png') . $html;
            } else {
                $html = Horde::img('tree/line' . $foreground . '.png', '|') . $html;
            }
            $parent = $parent->parent;
        }
        if ($this->indent) {
            if ($this->lastChild) {
                $html .= Horde::img($GLOBALS['registry']->nlsconfig->curr_rtl ? 'tree/rev-joinbottom' . $foreground . '.png' : 'tree/joinbottom' . $foreground . '.png', '\\');
            } else {
                $html .= Horde::img($GLOBALS['registry']->nlsconfig->curr_rtl ? 'tree/rev-join' . $foreground . '.png' : 'tree/join' . $foreground . '.png', '+');
            }
        }

        return $html;
    }

    /**
     * Recursively loads tags for all tasks contained in this object.
     */
    public function loadTags()
    {
        $ids = array();
        if (!isset($this->_tags)) {
            $ids[] = $this->uid;
        }
        foreach ($this->children as $task) {
            $ids[] = $task->uid;
        }
        if (!$ids) {
            return;
        }

        $results = $GLOBALS['injector']->getInstance('Nag_Tagger')->getTags($ids);

        if (isset($results[$this->uid])) {
            $this->synchronizeTags($results[$this->uid]);
        }
        foreach ($this->children as $task) {
            if (isset($results[$task->uid])) {
                $task->synchronizeTags($results[$task->uid]);
                $task->loadTags();
            }
        }
    }

    /**
     * Syncronizes tags from the tagging backend with the task storage backend,
     * if necessary.
     *
     * @param array $tags  Tags from the tagging backend.
     */
    public function synchronizeTags(array $tags)
    {
        if (isset($this->internaltags)) {
            $lower_internaltags = array_map('Horde_String::lower', $this->internaltags);
            $lower_tags = array_map('Horde_String::lower', $tags);

            if (array_diff($lower_internaltags, $lower_tags)) {
                $GLOBALS['injector']->getInstance('Nag_Tagger')->replaceTags(
                    $this->uid,
                    $this->internaltags,
                    $this->owner,
                    'task'
                );
            }
            $this->_tags = implode(',', $this->internaltags);
        } else {
            $this->_tags = $tags;
        }
    }

    /**
     * Sorts sub tasks by the given criteria.
     *
     * @param string $sortby     The field by which to sort
     *                           (Nag::SORT_PRIORITY, Nag::SORT_NAME
     *                           Nag::SORT_DUE, Nag::SORT_COMPLETION).
     * @param integer $sortdir   The direction by which to sort
     *                           (Nag::SORT_ASCEND, Nag::SORT_DESCEND).
     * @param string $altsortby  The secondary sort field.
     */
    public function sort($sortby, $sortdir, $altsortby)
    {
        /* Sorting criteria for the task list. */
        $sort_functions = array(
            Nag::SORT_PRIORITY => 'ByPriority',
            Nag::SORT_NAME => 'ByName',
            Nag::SORT_DUE => 'ByDue',
            Nag::SORT_START => 'ByStart',
            Nag::SORT_COMPLETION => 'ByCompletion',
            Nag::SORT_ASSIGNEE => 'ByAssignee',
            Nag::SORT_ESTIMATE => 'ByEstimate',
            Nag::SORT_OWNER => 'ByOwner'
        );

        /* Sort the array if we have a sort function defined for this
         * field. */
        if (isset($sort_functions[$sortby])) {
            $prefix = ($sortdir == Nag::SORT_DESCEND) ? '_rsort' : '_sort';
            usort($this->children, array('Nag', $prefix . $sort_functions[$sortby]));
            if (isset($sort_functions[$altsortby]) && $altsortby !== $sortby) {
                $task_buckets = array();
                for ($i = 0, $c = count($this->children); $i < $c; ++$i) {
                    if (!isset($task_buckets[$this->children[$i]->$sortby])) {
                        $task_buckets[$this->children[$i]->$sortby] = array();
                    }
                    $task_buckets[$this->children[$i]->$sortby][] = $this->children[$i];
                }
                $tasks = array();
                foreach ($task_buckets as $task_bucket) {
                    usort($task_bucket, array('Nag', $prefix . $sort_functions[$altsortby]));
                    $tasks = array_merge($tasks, $task_bucket);
                }
                $this->children = $tasks;
            }

            /* Mark last child. */
            for ($i = 0, $c = count($this->children); $i < $c; ++$i) {
                $this->children[$i]->lastChild = false;
            }
            if (count($this->children)) {
                $this->children[count($this->children) - 1]->lastChild = true;
            }

            for ($i = 0, $c = count($this->children); $i < $c; ++$i) {
                $this->_dict[$this->children[$i]->id] = $i;
                $this->children[$i]->sort($sortby, $sortdir, $altsortby);
            }
        }
    }

    /**
     * Returns a hash representation for this task.
     *
     * @return array  A task hash.
     */
    public function toHash()
    {
        $hash = [
            'tasklist_id' => $this->tasklist,
            'task_id' => $this->id,
            'uid' => $this->uid,
            'parent' => $this->parent_id,
            'owner' => $this->owner,
            'assignee' => $this->assignee,
            'name' => $this->name,
            'desc' => $this->desc,
            'start' => $this->start,
            'due' => $this->due,
            'priority' => $this->priority,
            'estimate' => $this->estimate,
            'completed' => $this->completed,
            'completed_date' => $this->completed_date,
            'alarm' => $this->alarm,
            'methods' => $this->methods,
            'private' => $this->private,
            'recurrence' => $this->recurrence,
            'tags' => $this->tags,
            'organizer' => $this->organizer,
            'status' => $this->status,
            'actual' => $this->actual,
            'other' => json_encode($this->otherCaldavAttributes)
        ];

        return $hash;
    }

    /**
     * Returns a simple object suitable for json transport representing this
     * task.
     *
     * @param boolean $full        Whether to return all task details.
     * @param string $time_format  The date() format to use for time formatting.
     *
     * @return object  A simple object.
     */
    public function toJson($full = false, $time_format = 'H:i')
    {
        $json = new stdClass;
        $json->l = $this->tasklist;
        $json->p = $this->parent_id;
        $json->i = $this->indent;
        $json->n = $this->name;
        $json->other = json_encode($this->otherCaldavAttributes);
        if ($this->desc) {
            //TODO: Get the proper amount of characters, and cut by last
            //whitespace
            $json->sd = Horde_String::substr($this->desc, 0, 80);
        }
        $json->cp = (boolean)$this->completed;
        if ($this->due && ($due = $this->getNextDue())) {
            $json->du = $due->toJson();
        }
        if ($this->start && ($start = $this->getNextStart())) {
            $json->s = $start->toJson();
        }
        $json->pr = (int)$this->priority;
        if ($this->recurs()) {
            $json->r = $this->recurrence->getRecurType();
        }
        $json->t = array_values($this->tags);

        if ($full) {
            // @todo: do we really need all this?
            $json->id = $this->id;
            $json->de = $this->desc;
            if ($this->due) {
                $date = new Horde_Date($this->due);
                $json->dd = $date->strftime('%x');
                $json->dt = $date->format($time_format);
            }
            $json->as = $this->assignee;
            if ($this->estimate) {
                $json->e = $this->estimate;
            }
            /*
            $json->o = $this->owner;

            if ($this->completed_date) {
                $date = new Horde_Date($this->completed_date);
                $json->cd = $date->toJson();
            }
            */
            $json->a = (int)$this->alarm;
            $json->m = $this->methods;
            //$json->pv = (boolean)$this->private;
            if ($this->recurs()) {
                $json->r = $this->recurrence->toJson();
            }

            if ($this->tasklist == '**EXTERNAL**') {
                $json->vl = (string)$this->view_link;
                $json->cl = (string)$this->complete_link;
                $json->pe = $json->pd = false;
            } else {
                try {
                    $share = $GLOBALS['nag_shares']->getShare($this->tasklist);
                } catch (Horde_Share_Exception $e) {
                    Horde::log($e->getMessage(), 'ERR');
                    throw new Nag_Exception($e);
                }
                $json->pe = $share->hasPermission(
                    $GLOBALS['registry']->getAuth(),
                    Horde_Perms::EDIT
                );
                $json->pd = $share->hasPermission(
                    $GLOBALS['registry']->getAuth(),
                    Horde_Perms::DELETE
                );
            }
        }

        return $json;
    }

    /**
     * Returns an alarm hash of this task suitable for Horde_Alarm.
     *
     * @param string $user  The user to return alarms for.
     * @param Prefs $prefs  A Prefs instance.
     *
     * @return array  Alarm hash or null.
     */
    public function toAlarm($user = null, $prefs = null)
    {
        if (empty($this->alarm) || $this->completed) {
            return;
        }

        if (empty($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }
        if (empty($prefs)) {
            $prefs = $GLOBALS['prefs'];
        }

        $methods = !empty($this->methods) ? $this->methods : @unserialize($prefs->getValue('task_alarms'));
        if (!$methods) {
            $methods = array();
        }

        if (isset($methods['notify'])) {
            $methods['notify']['show'] = array(
                '__app' => $GLOBALS['registry']->getApp(),
                'task' => $this->id,
                'tasklist' => $this->tasklist);
            $methods['notify']['ajax'] = 'task:' . $this->tasklist . ':' . $this->id;
            if (!empty($methods['notify']['sound'])) {
                if ($methods['notify']['sound'] == 'on') {
                    // Handle boolean sound preferences;
                    $methods['notify']['sound'] = (string)Horde_Themes::sound('theetone.wav');
                } else {
                    // Else we know we have a sound name that can be
                    // served from Horde.
                    $methods['notify']['sound'] = (string)Horde_Themes::sound($methods['notify']['sound']);
                }
            }
        }
        if (isset($methods['mail'])) {
            $image = Nag::getImagePart('big_alarm.png');

            $view = new Horde_View(array('templatePath' => NAG_TEMPLATES . '/alarm', 'encoding' => 'UTF-8'));
            new Horde_View_Helper_Text($view);
            $view->task = $this;
            $view->imageId = $image->getContentId();
            $view->due = new Horde_Date($this->due);
            $view->dateFormat = $prefs->getValue('date_format');
            $view->timeFormat = $prefs->getValue('twentyFour') ? 'H:i' : 'h:ia';
            if (!$prefs->isLocked('task_alarms')) {
                $view->prefsUrl = Horde::url($GLOBALS['registry']->getServiceLink('prefs', 'nag'), true)->remove(session_name());
            }

            $methods['mail']['mimepart'] = Nag::buildMimeMessage($view, 'mail', $image);
        }

        if (isset($methods['desktop'])) {
            $methods['desktop']['url'] = Horde::url('view.php', true)->add('tasklist', $this->tasklist)->add('task', $this->id)->toString(true, true);
        }

        return array(
            'id' => $this->uid,
            'user' => $user,
            'start' => new Horde_Date($this->due - $this->alarm * 60),
            'methods' => array_keys($methods),
            'params' => $methods,
            'title' => $this->name,
            'text' => $this->desc);
    }

    /**
     * Exports this task in iCalendar format.
     *
     * @param Horde_Icalendar $calendar  A Horde_Icalendar object that acts as
     *                                   the container.
     *
     * @return Horde_Icalendar_Vtodo  A vtodo component of this task.
     */
    public function toiCalendar(Horde_Icalendar $calendar)
    {
        $vTodo = Horde_Icalendar::newComponent('vtodo', $calendar);
        $v1 = $calendar->getAttribute('VERSION') == '1.0';

        $vTodo->setAttribute('UID', $this->uid);

        foreach ($this->otherCaldavAttributes as $attribute) {
            $vTodo->setAttribute($attribute['name'], $attribute['value'], $attribute['params'], true, $attribute['values']);
        }

        if (!empty($this->assignee)) {
            $vTodo->setAttribute('ATTENDEE', Nag::getUserEmail($this->assignee), array('ROLE' => 'REQ-PARTICIPANT'));
        }

        $vTodo->setAttribute('ORGANIZER', !empty($this->organizer) ? Nag::getUserEmail($this->organizer) : Nag::getUserEmail($this->owner));

        if (!empty($this->name)) {
            $vTodo->setAttribute('SUMMARY', $this->name);
        }

        if (!empty($this->desc)) {
            $vTodo->setAttribute('DESCRIPTION', $this->desc);
        }

        if (isset($this->priority)) {
            $priorityMap = array(
                0 => 5,
                1 => 1,
                2 => 3,
                3 => 5,
                4 => 7,
                5 => 9,
            );
            $vTodo->setAttribute('PRIORITY', $priorityMap[$this->priority]);
        }

        if (!empty($this->parent_id) && !empty($this->parent)) {
            $vTodo->setAttribute('RELATED-TO', $this->parent->uid);
        }

        if ($this->private) {
            $vTodo->setAttribute('CLASS', 'PRIVATE');
        }

        if (!empty($this->start)) {
            $vTodo->setAttribute('DTSTART', $this->start);
        }

        if ($this->due) {
            $vTodo->setAttribute('DUE', $this->due);

            if ($this->alarm) {
                if ($v1) {
                    $vTodo->setAttribute('AALARM', $this->due - $this->alarm * 60);
                } else {
                    $vAlarm = Horde_Icalendar::newComponent('valarm', $vTodo);
                    $vAlarm->setAttribute('ACTION', 'DISPLAY');
                    $vAlarm->setAttribute('DESCRIPTION', $this->name);
                    $vAlarm->setAttribute('TRIGGER;VALUE=DURATION', '-PT' . $this->alarm . 'M');
                    $vTodo->addComponent($vAlarm);
                }
                $hordeAlarm = $GLOBALS['injector']->getInstance('Horde_Alarm');
                if ($hordeAlarm->exists($this->uid, $GLOBALS['registry']->getAuth()) &&
                    $hordeAlarm->isSnoozed($this->uid, $GLOBALS['registry']->getAuth())) {
                    $vTodo->setAttribute('X-MOZ-LASTACK', new Horde_Date($_SERVER['REQUEST_TIME']));
                    $alarm = $hordeAlarm->get($this->uid, $GLOBALS['registry']->getAuth());
                    if (!empty($alarm['snooze'])) {
                        $alarm['snooze']->setTimezone(date_default_timezone_get());
                        $vTodo->setAttribute('X-MOZ-SNOOZE-TIME', $alarm['snooze']);
                    }
                }
            }
        }

        if ($this->completed) {
            $vTodo->setAttribute('STATUS', 'COMPLETED');
            $vTodo->setAttribute('COMPLETED', $this->completed_date ? $this->completed_date : $_SERVER['REQUEST_TIME']);
            $vTodo->setAttribute('PERCENT-COMPLETE', '100');
        } else {
            if (!empty($this->estimate)) {
                $vTodo->setAttribute('PERCENT-COMPLETE', ($this->actual / $this->estimate) * 100);
            }
            if ($v1) {
                $vTodo->setAttribute('STATUS', 'NEEDS ACTION');
            } else {
                $vTodo->setAttribute('STATUS', 'NEEDS-ACTION');
            }
        }
        if (!empty($this->estimate)) {
            $vTodo->setAttribute('X-HORDE-ESTIMATE', $this->estimate);
        }
        if (!empty($this->actual)) {
            $vTodo->setAttribute('X-HORDE-EFFORT', $this->actual);
        }

        // Recurrence.
        // We may have to implicitely set DTSTART if not set explicitely, may
        // some clients choke on missing DTSTART attributes while RRULE exists.
        if ($this->recurs()) {
            if ($v1) {
                $rrule = $this->recurrence->toRRule10($calendar);
            } else {
                $rrule = $this->recurrence->toRRule20($calendar);
            }
            if (!empty($rrule)) {
                $vTodo->setAttribute('RRULE', $rrule);
            }

            /* The completions represent deleted recurrences */
            foreach ($this->recurrence->getCompletions() as $exception) {
                if (!empty($exception)) {
                    // Use multiple EXDATE attributes instead of EXDATE
                    // attributes with multiple values to make Apple iCal
                    // happy.
                    list($year, $month, $mday) = sscanf($exception, '%04d%02d%02d');
                    $vTodo->setAttribute('EXDATE', array(new Horde_Date($year, $month, $mday)), array('VALUE' => 'DATE'));
                }
            }
        }

        if ($this->tags) {
            $vTodo->setAttribute('CATEGORIES', '', array(), true, array_values($this->tags));
        }

        /* Get the task's history. */
        $created = $modified = null;
        try {
            $log = $GLOBALS['injector']->getInstance('Horde_History')->getHistory('nag:' . $this->tasklist . ':' . $this->uid);
            foreach ($log as $entry) {
                switch ($entry['action']) {
                case 'add':
                    $created = $entry['ts'];
                    break;

                case 'modify':
                    $modified = $entry['ts'];
                    break;
                }
            }
        } catch (Exception $e) {}
        if (!empty($created)) {
            $vTodo->setAttribute($v1 ? 'DCREATED' : 'CREATED', $created);
            if (empty($modified)) {
                $modified = $created;
            }
        }
        if (!empty($modified)) {
            $vTodo->setAttribute('LAST-MODIFIED', $modified);
        }

        return $vTodo;
    }

    /**
     * Create an AS message from this task
     *
     * @param array $options  Options:
     *   - protocolversion: (float)  The EAS version to support
     *                      DEFAULT: 2.5
     *   - bodyprefs: (array)  A BODYPREFERENCE array.
     *                DEFAULT: none (No body prefs enforced).
     *   - truncation: (integer)  Truncate event body to this length
     *                 DEFAULT: none (No truncation).
     *
     * @return Horde_ActiveSync_Message_Task
     */
    public function toASTask(array $options = array())
    {
        $message = new Horde_ActiveSync_Message_Task(array(
            'protocolversion' => $options['protocolversion'])
        );

        /* Notes and Title */
        if ($options['protocolversion'] >= Horde_ActiveSync::VERSION_TWELVE) {
            if (!empty($this->desc)) {
                $bp = $options['bodyprefs'];
                $body = new Horde_ActiveSync_Message_AirSyncBaseBody();
                $body->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
                if (isset($bp[Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'])) {
                    $truncation = $bp[Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'];
                } elseif (isset($bp[Horde_ActiveSync::BODYPREF_TYPE_HTML])) {
                    $truncation = $bp[Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'];
                    $this->desc = Horde_Text_Filter::filter($this->desc, 'Text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
                } else {
                    $truncation = false;
                }
                if ($truncation && Horde_String::length($this->desc) > $truncation) {
                    $body->data = Horde_String::substr($this->desc, 0, $truncation);
                    $body->truncated = 1;
                } else {
                    $body->data = $this->desc;
                }
                $body->estimateddatasize = Horde_String::length($this->desc);
                $message->airsyncbasebody = $body;
            }
        } else {
            $message->body = $this->desc;
        }
        $message->subject = $this->name;

        /* Completion */
        if ($this->completed) {
            if ($this->completed_date) {
                $message->datecompleted = new Horde_Date($this->completed_date);
            }
            $message->complete = Horde_ActiveSync_Message_Task::TASK_COMPLETE_TRUE;
        } else {
            $message->complete = Horde_ActiveSync_Message_Task::TASK_COMPLETE_FALSE;
        }

        /* Due Date */
        if (!empty($this->due)) {
            if ($this->due) {
                $message->utcduedate = new Horde_Date($this->getNextDue());
            }
            $message->duedate = clone($message->utcduedate);
        }

        /* Start Date */
        if (!empty($this->start)) {
            if ($this->start) {
                $message->utcstartdate = new Horde_Date($this->start);
            }
            $message->startdate = clone($message->utcstartdate);
        }

        /* Priority */
        switch ($this->priority) {
        case 5:
            $priority = Horde_ActiveSync_Message_Task::IMPORTANCE_LOW;
            break;
        case 4:
        case 3:
        case 2:
            $priority = Horde_ActiveSync_Message_Task::IMPORTANCE_NORMAL;
            break;
        case 1:
            $priority = Horde_ActiveSync_Message_Task::IMPORTANCE_HIGH;
            break;
        default:
            $priority = Horde_ActiveSync_Message_Task::IMPORTANCE_NORMAL;
        }
        $message->setImportance($priority);

        /* Reminders */
        if ($this->due && $this->alarm) {
            $message->setReminder(new Horde_Date($this->due - $this->alarm * 60));
        }

        /* Recurrence */
        if ($this->recurs()) {
            $message->setRecurrence($this->recurrence);
        }

        /* Categories */
        $message->categories = $this->tags;

        return $message;
    }

    /**
     * Creates a task from a Horde_Icalendar_Vtodo object.
     *
     * @param Horde_Icalendar_Vtodo $vTodo  The iCalendar data to update from.
     */
    public function fromiCalendar(Horde_Icalendar_Vtodo $vTodo)
    {
        /* Owner is always current user. */
        $this->owner = $GLOBALS['registry']->getAuth();

        try {
            $name = $vTodo->getAttribute('SUMMARY');
            if (!is_array($name)) {
                $this->name = $name;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        // Not sure why we were mapping the ORGANIZER to the person the
        // task is assigned to? If anything, this needs to be mapped to
        // any ATTENDEE fields from the vTodo.
        // try {
        //     $assignee = $vTodo->getAttribute('ORGANIZER');
        //     if (!is_array($assignee)) { $this->assignee = $assignee; }
        // } catch (Horde_Icalendar_Exception $e) {}

        try {
            $organizer = $vTodo->getAttribute('ORGANIZER');
            if (!is_array($organizer)) {
                $this->organizer = $organizer;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        // If an attendee matches our from_addr, add current user as assignee.
        try {
            $atnames = $vTodo->getAttribute('ATTENDEE');
            if (!is_array($atnames)) {
                $atnames = array($atnames);
            }
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
            $all_addrs = $identity->getAll('from_addr');
            foreach ($atnames as $index => $attendee) {
                if ($vTodo->getAttribute('VERSION') < 2) {
                    $addr_ob = new Horde_Mail_Rfc822_Address($attendee);
                    if (!$addr_ob->valid) {
                        continue;
                    }
                    $attendee = $addr_ob->bare_address;
                    $name = $addr_ob->personal;
                } else {
                    $attendee = str_ireplace('mailto:', '', $attendee);
                    $addr_ob = new Horde_Mail_Rfc822_Address($attendee);
                    if (!$addr_ob->valid) {
                        continue;
                    }
                    $attendee = $addr_ob->bare_address;
                    $name = isset($atparms[$index]['CN']) ? $atparms[$index]['CN'] : null;
                }
                if (in_array($attendee, $all_addrs) !== false) {
                    $this->assignee = $GLOBALS['conf']['assignees']['allow_external'] ? $attendee : $GLOBALS['registry']->getAuth();
                    $this->status = Nag::RESPONSE_ACCEPTED;
                    break;
                } elseif ($GLOBALS['conf']['assignees']['allow_external']) {
                    $this->assignee = $attendee;
                }
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        // Default to current user as organizer
        if (empty($this->organizer) && !empty($this->assignee)) {
            $this->organizer = $identity->getValue('from_addr');
        }

        try {
            $uid = $vTodo->getAttribute('UID');
            if (!is_array($uid)) { $this->uid = $uid; }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $relations = $vTodo->getAttribute('RELATED-TO');
            if (!is_array($relations)) {
                $relations = array($relations);
            }
            $params = $vTodo->getAttribute('RELATED-TO', true);
            foreach ($relations as $id => $relation) {
                if (empty($params[$id]['RELTYPE']) ||
                    Horde_String::upper($params[$id]['RELTYPE']) == 'PARENT') {
                    try {
                        // Shouldn't this rather be [this->tasklist]?
                        $parent = $this->_storage->getByUID($relation, $this->tasklist);
                        $this->parent_id = $parent->id;
                    } catch (Horde_Exception_NotFound $e) {
                    }
                    break;
                }
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $start = $vTodo->getAttribute('DTSTART');
            if (!is_array($start)) {
                // Date-Time field
                $this->start = $start;
            } else {
                // Date field
                $this->start = mktime(0, 0, 0, (int)$start['month'], (int)$start['mday'], (int)$start['year']);
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $due = $vTodo->getAttribute('DUE');
            if (is_array($due)) {
                $this->due = mktime(0, 0, 0, (int)$due['month'], (int)$due['mday'], (int)$due['year']);
            } elseif (!empty($due)) {
                $this->due = $due;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        // Recurrence.
        try {
            $rrule = $vTodo->getAttribute('RRULE');
            if (!is_array($rrule)) {
                $this->recurrence = new Horde_Date_Recurrence($this->due);
                if (strpos($rrule, '=') !== false) {
                    $this->recurrence->fromRRule20($rrule);
                } else {
                    $this->recurrence->fromRRule10($rrule);
                }

                // Completions. EXDATE represents completed tasks, just add the
                // exception.
                $exdates = $vTodo->getAttributeValues('EXDATE');
                if (is_array($exdates)) {
                    foreach ($exdates as $exdate) {
                        if (is_array($exdate)) {
                            $this->recurrence->addCompletion(
                                (int)$exdate['year'],
                                (int)$exdate['month'],
                                (int)$exdate['mday']);
                        }
                    }
                }
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        // vCalendar 1.0 alarms
        try {
            $alarm = $vTodo->getAttribute('AALARM');
            if (!is_array($alarm) && !empty($alarm) && !empty($this->due)) {
                $this->alarm = intval(($this->due - $alarm) / 60);
                if ($this->alarm === 0) {
                    // We don't support alarms exactly at due date.
                    $this->alarm = 1;
                }
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        // vCalendar 2.0 alarms
        foreach ($vTodo->getComponents() as $alarm) {
            if (!($alarm instanceof Horde_Icalendar_Valarm)) {
                continue;
            }
            try {
                if ($alarm->getAttribute('ACTION') == 'NONE') {
                    continue;
                }
            } catch (Horde_Icalendar_Exception $e) {
            }
            try {
                // @todo consider implementing different ACTION types.
                // $action = $alarm->getAttribute('ACTION');
                $trigger = $alarm->getAttribute('TRIGGER');
                $triggerParams = $alarm->getAttribute('TRIGGER', true);
            } catch (Horde_Icalendar_Exception $e) {
                continue;
            }
            if (!is_array($triggerParams)) {
                $triggerParams = array($triggerParams);
            }
            $haveTrigger = false;
            foreach ($triggerParams as $tp) {
                if (isset($tp['VALUE']) &&
                    $tp['VALUE'] == 'DATE-TIME') {
                    if (isset($tp['RELATED']) &&
                        $tp['RELATED'] == 'END') {
                        if ($this->due) {
                            $this->alarm = intval(($this->due - $trigger) / 60);
                            $haveTrigger = true;
                            break;
                        }
                    } else {
                        if ($this->start) {
                            $this->alarm = intval(($this->start - $trigger) / 60);
                            $haveTrigger = true;
                            break;
                        }
                    }
                } elseif (isset($tp['RELATED']) && $tp['RELATED'] == 'END' &&
                          $this->due && $this->start) {
                    $this->alarm = -intval($trigger / 60);
                    $this->alarm -= ($this->due - $this->start);
                    $haveTrigger = true;
                    break;
                }
            }
            if (!$haveTrigger) {
                $this->alarm = -intval($trigger / 60);
            }
            break;
        }

        // Alarm snoozing/dismissal
        if ($this->alarm) {
            try {
                // If X-MOZ-LASTACK is set, this task is either dismissed or
                // snoozed.
                $vTodo->getAttribute('X-MOZ-LASTACK');
                try {
                    // If X-MOZ-SNOOZE-TIME is set, this task is snoozed.
                    $snooze = $vTodo->getAttribute('X-MOZ-SNOOZE-TIME');
                    $this->snooze = intval(($snooze - time()) / 60);
                } catch (Horde_Icalendar_Exception $e) {
                    // If X-MOZ-SNOOZE-TIME is not set, this event is dismissed.
                    $this->snooze = -1;
                }
            } catch (Horde_Icalendar_Exception $e) {
            }
        }

        try {
            $desc = $vTodo->getAttribute('DESCRIPTION');
            if (!is_array($desc)) {
                $this->desc = $desc;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $priority = $vTodo->getAttribute('PRIORITY');
            if (!is_array($priority)) {
                $priorityMap = array(
                    0 => 3,
                    1 => 1,
                    2 => 1,
                    3 => 2,
                    4 => 2,
                    5 => 3,
                    6 => 4,
                    7 => 4,
                    8 => 5,
                    9 => 5,
                );
                $this->priority = isset($priorityMap[$priority])
                    ? $priorityMap[$priority]
                    : 3;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $cat = $vTodo->getAttribute('CATEGORIES');
            if (!is_array($cat)) {
                $this->tags = $cat;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $status = $vTodo->getAttribute('STATUS');
            if (!is_array($status)) {
                $this->completed = !strcasecmp($status, 'COMPLETED');
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $class = $vTodo->getAttribute('CLASS');
            if (!is_array($class)) {
                $class = Horde_String::upper($class);
                $this->private = $class == 'PRIVATE' || $class == 'CONFIDENTIAL';
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $estimate = $vTodo->getAttribute('X-HORDE-ESTIMATE');
            if (!is_array($estimate)) {
                $this->estimate = $estimate;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $effort = $vTodo->getAttribute('X-HORDE-EFFORT');
            if (!is_array($effort)) {
                $this->actual = $effort;
            }
        } catch (Horde_Icalendar_Exception $e) {
        }
        // Catch attributes nag is not aware of
        try {
            foreach ($vTodo->getAllAttributes() as $attribute) {
            // drop all known attributes
            if (in_array($attribute['name'], $this->ourCaldavAttributes)) {
                continue;
            }
            $this->otherCaldavAttributes[] = $attribute;

            }
        } catch (Horde_Icalendar_Exception $e) {
        }
    }

    /**
     * Create a nag Task object from an activesync message
     *
     * @param Horde_ActiveSync_Message_Task $message  The task object
     */
    public function fromASTask(Horde_ActiveSync_Message_Task $message)
    {
        /* Owner is always current user. */
        $this->owner = $GLOBALS['registry']->getAuth();

        /* Must set _tags so we don't lazy load tags from the backend in the
         * case that this is an edit. For edits, all current tags will be passed
         * from the client.
         */
        $this->_tags = array();

        /* Notes and Title */
        if ($message->getProtocolVersion() >= Horde_ActiveSync::VERSION_TWELVE) {
            if ($message->airsyncbasebody->type == Horde_ActiveSync::BODYPREF_TYPE_HTML) {
                $this->desc = Horde_Text_Filter::filter($message->airsyncbasebody->data, 'Html2text');
            } else {
                $this->desc = $message->airsyncbasebody->data;
            }
        } else {
            $this->desc = $message->body;
        }

        $this->name = $message->subject;
        $tz = date_default_timezone_get();

        /* Completion: Note we don't use self::toggleCompletion() becuase of
         * the way that EAS hanldes recurring tasks (see below). */
        if ($this->completed = $message->complete) {
            if ($message->datecompleted) {
                $message->datecompleted->setTimezone($tz);
                $this->completed_date = $message->datecompleted->timestamp();
            } else {
                $this->completed_date = null;
            }
        }

        /* Due Date */
        if ($due = $message->utcduedate) {
            $due->setTimezone($tz);
            $this->due = $due->timestamp();
        } elseif ($due = $message->duedate) {
            // "Local" date, sent as a UTC datetime string,
            // but must be interpreted as a local time. Since
            // we have no timezone information we have to assume it's the
            // same as $tz.
            $due = new Horde_Date(
                array(
                    'year' => $due->year,
                    'month' => $due->month,
                    'mday' => $due->mday,
                    'hour' => $due->hour,
                    'min' => $due->min
                ),
                $tz
            );
            $this->due = $due->timestamp();
        }

        /* Start Date */
        if ($start = $message->utcstartdate) {
            $start->setTimezone($tz);
            $this->start = $start->timestamp();
        } elseif ($start = $message->startdate) {
            // See note above regarding utc vs local times.
            $start = new Horde_Date(
                array(
                    'year' => $start->year,
                    'month' => $start->month,
                    'mday' => $start->mday,
                    'hour' => $start->hour,
                    'min' => $start->min
                ),
                $tz
            );
            $this->start = $start->timestamp();
        }

        /* Priority */
        switch ($message->getImportance()) {
        case Horde_ActiveSync_Message_Task::IMPORTANCE_LOW:
            $this->priority = 5;
            break;
        case Horde_ActiveSync_Message_Task::IMPORTANCE_NORMAL:
            $this->priority = 3;
            break;
        case Horde_ActiveSync_Message_Task::IMPORTANCE_HIGH:
            $this->priority = 1;
            break;
        default:
            $this->priority = 3;
        }

        if (($alarm = $message->getReminder()) && $this->due) {
            $alarm->setTimezone($tz);
            $this->alarm = ($this->due - $alarm->timestamp()) / 60;
        }

        $this->tasklist = $GLOBALS['prefs']->getValue('default_tasklist');

        /* Categories */
        if (is_array($message->categories) && count($message->categories)) {
            $this->tags = implode(',', $message->categories);
        }

        // Recurrence is handled by the client deleting the original event
        // and recreating a "dead" completed event and an active recurring
        // event with the first due date being the next due date in the
        // series. So, if deadoccur is set, we have to ignore the recurrence
        // properties. Otherwise, editing the "dead" occurance will recreate
        // a completely new recurring series on the client.
        if (!($message->recurrence && $message->recurrence->deadoccur) &&
            !$message->deadoccur) {

            if ($rrule = $message->getRecurrence()) {
                $this->recurrence = $rrule;
            }
        }
    }

}
