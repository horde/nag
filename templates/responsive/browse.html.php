<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo _("Tasks") ?> - Horde</title>

    <!-- Responsive styles (cascade: horde base + app) -->
    <?php foreach ($cssUrls as $url): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url) ?>">
    <?php endforeach; ?>
</head>
<body>
    <!-- Topbar -->
    <?php echo $topbarHtml ?>

    <!-- Main content -->
    <div class="container nag-container">
        <!-- Page header -->
        <div class="page-header">
            <h1><?php echo _("My Tasks") ?></h1>
            <a href="<?php echo $escape(Horde::url('responsive/add')) ?>" class="add-task-btn">
                <span class="add-icon">+</span>
                <?php echo _("New Task") ?>
            </a>
        </div>

        <!-- Filter controls -->
        <div class="filter-controls">
            <select id="task-filter" class="filter-select" onchange="window.location.href='<?php echo $escape(Horde::url('responsive')) ?>/' + this.value">
                <option value="all" <?php echo $filter == Nag::VIEW_ALL ? 'selected' : '' ?>><?php echo _("All Tasks") ?></option>
                <option value="incomplete" <?php echo $filter == Nag::VIEW_INCOMPLETE ? 'selected' : '' ?>><?php echo _("Incomplete") ?></option>
                <option value="complete" <?php echo $filter == Nag::VIEW_COMPLETE ? 'selected' : '' ?>><?php echo _("Complete") ?></option>
                <option value="future" <?php echo $filter == Nag::VIEW_FUTURE ? 'selected' : '' ?>><?php echo _("Future") ?></option>
                <option value="future-incomplete" <?php echo $filter == Nag::VIEW_FUTURE_INCOMPLETE ? 'selected' : '' ?>><?php echo _("Future Incomplete") ?></option>
            </select>
        </div>

        <!-- Task lists -->
        <?php if (empty($groupedTasks)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✓</div>
                <h2><?php echo _("No tasks to display") ?></h2>
                <p><?php echo _("Create a new task to get started") ?></p>
            </div>
        <?php else: ?>
            <div id="task-lists">
                <?php foreach ($groupedTasks as $tasklistId => $taskList): ?>
                    <details class="task-list" open>
                        <summary class="tasklist-header">
                            <span class="tasklist-title"><?php echo $escape($taskList['name']) ?></span>
                            <span class="tasklist-count"><?php echo $taskList['count'] ?></span>
                        </summary>

                        <ul class="task-items">
                            <?php foreach ($taskList['tasks'] as $task): ?>
                                <li class="task-item <?php echo $task['completed'] ? 'task-completed' : '' ?> <?php echo isset($task['overdue']) && $task['overdue'] ? 'task-overdue' : '' ?>">
                                    <!-- Complete checkbox -->
                                    <form method="POST" action="<?php echo Horde::url('responsive')->add(['action' => 'complete', 'id' => $task['id'], 'tasklist' => $task['tasklist']]) ?>" style="display:inline;">
                                        <button type="submit" class="task-complete-btn" onclick="return confirm('<?php echo $task['completed'] ? _("Mark as incomplete?") : _("Mark as complete?") ?>')">
                                            <span class="complete-checkbox"><?php echo $task['completed'] ? '☑' : '☐' ?></span>
                                        </button>
                                    </form>

                                    <!-- Task link -->
                                    <a href="<?php echo $escape(Horde::url('responsive/task/' . $task['tasklist'] . '/' . $task['id'])) ?>"
                                       class="task-link">
                                        <div class="task-info">
                                            <div class="task-name <?php echo $task['completed'] ? 'strikethrough' : '' ?>">
                                                <?php echo $escape($task['name']) ?>
                                            </div>
                                            <div class="task-meta">
                                                <?php if (isset($task['dueFormatted'])): ?>
                                                    <span class="task-due <?php echo isset($task['overdue']) && $task['overdue'] ? 'overdue' : '' ?>">
                                                        📅 <?php echo $escape($task['dueFormatted']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($task['priority'] <= 2): ?>
                                                    <span class="task-priority priority-<?php echo $task['priority'] ?>">
                                                        ⚡ <?php echo $task['priority'] == 1 ? _("Highest") : _("High") ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <span class="task-chevron">›</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Responsive JavaScript -->
    <?php foreach ($jsUrls as $url): ?>
    <script src="<?php echo htmlspecialchars($url) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
