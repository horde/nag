<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $escape($task['name']) ?> - <?php echo _("Tasks") ?></title>

    <!-- Responsive styles (cascade: horde base + app) -->
    <?php foreach ($cssUrls as $url): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url) ?>">
    <?php endforeach; ?>
</head>
<body>
    <?php echo $topbarHtml ?>

    <div class="container nag-container">
        <!-- Back link -->
        <a href="<?php echo $escape(Horde::url('responsive')) ?>" class="back-link">
            <span class="back-arrow">←</span>
            <?php echo _("Back to Tasks") ?>
        </a>

        <!-- Task detail card -->
        <div class="task-detail">
            <!-- Task name header -->
            <h1 class="task-name-header <?php echo $task['completed'] ? 'strikethrough' : '' ?>">
                <?php echo $escape($task['name']) ?>
                <?php if ($task['completed']): ?>
                    <span class="completion-badge"><?php echo _("Completed") ?></span>
                <?php endif; ?>
            </h1>

            <!-- Quick actions -->
            <div class="quick-actions">
                <form method="POST" action="<?php echo Horde::url('responsive')->add(['action' => 'complete', 'id' => $task['id'], 'tasklist' => $task['tasklist']]) ?>" style="display:inline;">
                    <button type="submit" class="quick-action-btn quick-action-complete">
                        <span class="quick-action-icon"><?php echo $task['completed'] ? '☐' : '☑' ?></span>
                        <span class="quick-action-label"><?php echo $task['completed'] ? _("Mark Incomplete") : _("Mark Complete") ?></span>
                    </button>
                </form>

                <?php if ($canEdit): ?>
                    <a href="<?php echo $escape(Horde::url('responsive/edit/' . $task['tasklist'] . '/' . $task['id'])) ?>"
                       class="quick-action-btn quick-action-edit">
                        <span class="quick-action-icon">✎</span>
                        <span class="quick-action-label"><?php echo _("Edit") ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($canDelete): ?>
                    <form method="POST" action="<?php echo Horde::url('responsive')->add(['action' => 'delete', 'id' => $task['id'], 'tasklist' => $task['tasklist']]) ?>" style="display:inline;">
                        <button type="submit" class="quick-action-btn quick-action-delete"
                           onclick="return confirm('<?php echo _("Really delete this task?") ?>')">
                            <span class="quick-action-icon">🗑</span>
                            <span class="quick-action-label"><?php echo _("Delete") ?></span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Task details -->
            <details class="task-section" open>
                <summary class="section-header"><?php echo _("Details") ?></summary>
                <div class="section-content">
                    <div class="task-field">
                        <div class="field-label"><?php echo _("Task List") ?></div>
                        <div class="field-value"><?php echo $escape($tasklistName) ?></div>
                    </div>

                    <?php if (isset($task['dueFormatted'])): ?>
                        <div class="task-field">
                            <div class="field-label"><?php echo _("Due Date") ?></div>
                            <div class="field-value <?php echo isset($task['overdue']) && $task['overdue'] ? 'text-error' : '' ?>">
                                <?php echo $escape($task['dueFormatted']) ?>
                                <?php if (isset($task['overdue']) && $task['overdue']): ?>
                                    <span class="overdue-badge"><?php echo _("Overdue") ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($task['startFormatted'])): ?>
                        <div class="task-field">
                            <div class="field-label"><?php echo _("Start Date") ?></div>
                            <div class="field-value"><?php echo $escape($task['startFormatted']) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="task-field">
                        <div class="field-label"><?php echo _("Priority") ?></div>
                        <div class="field-value">
                            <span class="priority-badge priority-<?php echo $task['priority'] ?>">
                                <?php
                                $priorities = [1 => _("Highest"), 2 => _("High"), 3 => _("Normal"), 4 => _("Low"), 5 => _("Lowest")];
    echo $escape($priorities[$task['priority']] ?? _("Normal"));
    ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($task['estimate'])): ?>
                        <div class="task-field">
                            <div class="field-label"><?php echo _("Estimate") ?></div>
                            <div class="field-value"><?php echo $escape($task['estimate']) ?> <?php echo _("hours") ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($task['private']): ?>
                        <div class="task-field">
                            <div class="field-label"><?php echo _("Privacy") ?></div>
                            <div class="field-value">🔒 <?php echo _("Private") ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($task['organizer'])): ?>
                        <div class="task-field">
                            <div class="field-label"><?php echo _("Organizer") ?></div>
                            <div class="field-value"><?php echo $escape($task['organizerFormatted'] ?: $task['organizer']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($task['assignee'])): ?>
                        <div class="task-field">
                            <div class="field-label"><?php echo _("Assignee") ?></div>
                            <div class="field-value">👤 <?php echo $escape($task['assigneeFormatted'] ?: $task['assignee']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($task['owner'])): ?>
                        <div class="task-field">
                            <div class="field-label"><?php echo _("Owner") ?></div>
                            <div class="field-value"><?php echo $escape($task['owner']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </details>

            <!-- Description -->
            <?php if (!empty($task['description'])): ?>
                <details class="task-section" open>
                    <summary class="section-header"><?php echo _("Description") ?></summary>
                    <div class="section-content">
                        <div class="task-description">
                            <?php echo nl2br($escape($task['description'])) ?>
                        </div>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </div>

    <!-- Responsive JavaScript -->
    <?php foreach ($jsUrls as $url): ?>
    <script src="<?php echo htmlspecialchars($url) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
