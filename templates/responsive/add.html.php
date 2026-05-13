<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($isEdit) ? _("Edit Task") : _("New Task") ?> - Horde</title>

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

        <!-- Form container -->
        <div class="task-form-container">
            <h1><?php echo isset($isEdit) ? _("Edit Task") : _("New Task") ?></h1>

            <form method="post"
                  action="<?php echo $escape(Horde::url('responsive')->add('action', isset($isEdit) ? 'update' : 'create')) ?>"
                  class="task-form">

                <?php if (isset($isEdit)): ?>
                    <input type="hidden" name="task_id" value="<?php echo $escape($task['id']) ?>">
                    <input type="hidden" name="original_tasklist" value="<?php echo $escape($task['tasklist']) ?>">
                <?php endif; ?>

                <!-- Task name -->
                <div class="form-group">
                    <label for="task-name" class="form-label">
                        <?php echo _("Task Name") ?> <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="task-name"
                           name="name"
                           class="form-control"
                           value="<?php echo isset($task) ? $escape($task['name']) : '' ?>"
                           required
                           autofocus>
                </div>

                <!-- Task list -->
                <div class="form-group">
                    <label for="task-list" class="form-label"><?php echo _("Task List") ?></label>
                    <select id="task-list" name="tasklist" class="form-control">
                        <?php foreach ($taskLists as $id => $list): ?>
                            <option value="<?php echo $escape($id) ?>"
                                    <?php echo (isset($task) && $task['tasklist'] == $id) || (!isset($task) && $id == $defaultTasklist) ? 'selected' : '' ?>>
                                <?php echo $escape($list->get('name')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Due date -->
                <div class="form-group">
                    <label for="task-due" class="form-label"><?php echo _("Due Date") ?></label>
                    <input type="date"
                           id="task-due"
                           name="due"
                           class="form-control"
                           value="<?php echo isset($task['due']) ? $escape($task['due']) : '' ?>">
                </div>

                <!-- Priority -->
                <div class="form-group">
                    <label for="task-priority" class="form-label"><?php echo _("Priority") ?></label>
                    <select id="task-priority" name="priority" class="form-control">
                        <?php foreach ($priorities as $value => $label): ?>
                            <option value="<?php echo $value ?>"
                                    <?php echo (isset($task) && $task['priority'] == $value) || (!isset($task) && $value == 3) ? 'selected' : '' ?>>
                                <?php echo $escape($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="task-description" class="form-label"><?php echo _("Description") ?></label>
                    <textarea id="task-description"
                              name="description"
                              class="form-control"
                              rows="5"><?php echo isset($task['description']) ? $escape($task['description']) : '' ?></textarea>
                </div>

                <!-- Start date -->
                <div class="form-group">
                    <label for="task-start" class="form-label"><?php echo _("Start Date") ?></label>
                    <input type="date"
                           id="task-start"
                           name="start"
                           class="form-control"
                           value="<?php echo isset($task['start']) ? $escape($task['start']) : '' ?>">
                </div>

                <!-- Estimate -->
                <div class="form-group">
                    <label for="task-estimate" class="form-label"><?php echo _("Estimate (hours)") ?></label>
                    <input type="number"
                           id="task-estimate"
                           name="estimate"
                           class="form-control"
                           min="0"
                           step="0.5"
                           value="<?php echo isset($task['estimate']) ? $escape($task['estimate']) : '' ?>">
                </div>

                <!-- Assignee -->
                <div class="form-group">
                    <label for="task-assignee" class="form-label">
                        <?php echo _("Assignee") ?>
                        <span class="form-help"><?php echo _("Email or name of person to assign this task to") ?></span>
                    </label>
                    <input type="text"
                           id="task-assignee"
                           name="assignee"
                           class="form-control"
                           placeholder="<?php echo _("name@example.com or Full Name") ?>"
                           value="<?php echo isset($task['assignee']) ? $escape($task['assignee']) : '' ?>">
                </div>

                <!-- Private checkbox -->
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox"
                               name="private"
                               <?php echo isset($task) && $task['private'] ? 'checked' : '' ?>>
                        <span><?php echo _("Private Task") ?></span>
                    </label>
                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo isset($isEdit) ? _("Update Task") : _("Create Task") ?>
                    </button>
                    <a href="<?php echo $escape(Horde::url('responsive')) ?>" class="btn btn-secondary">
                        <?php echo _("Cancel") ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Responsive JavaScript -->
    <?php foreach ($jsUrls as $url): ?>
    <script src="<?php echo htmlspecialchars($url) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
