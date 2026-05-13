<?php

/**
 * Nag Responsive Controller
 *
 * Handles responsive (mobile-first) views for Nag tasks application.
 * Provides browse, detail, add, edit, and action handlers for tasks.
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */

declare(strict_types=1);

namespace Horde\Nag\Responsive;

use Horde\Core\Controller\ResponsiveControllerTrait;
use Horde_Registry;
use Horde_Variables;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nag;
use Nag_Driver;
use Exception;
use Horde;
use Horde_Date;
use Horde_Perms;
use Horde_Share_Exception;
use Nag_CompleteTask;
use Nag_Exception;

/**
 * Nag Responsive Controller
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class ResponsiveController implements RequestHandlerInterface
{
    use ResponsiveControllerTrait;

    public function __construct(
        private Horde_Registry $registry,
        private UriFactoryInterface $uriFactory,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory
    ) {}

    /**
     * Get application name for topbar display
     */
    protected function getAppName(): string
    {
        return _("Tasks");
    }

    /**
     * Get template base path for this application
     */
    protected function getTemplateBasePath(): string
    {
        return NAG_TEMPLATES . '/responsive/';
    }

    /**
     * Get Horde registry instance
     */
    protected function getRegistry(): Horde_Registry
    {
        return $this->registry;
    }

    /**
     * Get PSR-7 URI factory instance
     */
    protected function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }

    /**
     * Get PSR-7 response factory instance
     */
    protected function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * Get PSR-7 stream factory instance
     */
    protected function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * Build application URL using PSR-7 UriFactory (inherited from trait)
     *
     * Note: This method is provided by ResponsiveControllerTrait.
     * Uses dependency-injected UriFactory for proper URL construction.
     */

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        global $notification;

        // Get route parameters from request attributes
        $route = $request->getAttribute('route', []);
        $path = $request->getUri()->getPath();

        // Check for POST actions (create, update, delete, complete)
        if ($request->getMethod() === 'POST') {
            $params = $request->getQueryParams();
            $action = $params['action'] ?? '';

            switch ($action) {
                case 'create':
                    return $this->create($request);
                case 'update':
                    return $this->update($request);
                case 'delete':
                    return $this->delete($request);
                case 'complete':
                    return $this->complete($request);
            }
        }

        // Route based on path
        if (isset($route['tasklist']) && isset($route['id'])) {
            // Check if this is an edit request
            if (str_contains($path, '/responsive/edit/')) {
                return $this->edit($request, $route['tasklist'], $route['id']);
            }
            // Task detail view
            return $this->task($request, $route['tasklist'], $route['id']);
        }

        // Add task form
        if (str_contains($path, '/responsive/add')) {
            return $this->add($request);
        }

        // Filter routes
        if (str_contains($path, '/responsive/incomplete')) {
            return $this->browse($request, Nag::VIEW_INCOMPLETE);
        }
        if (str_contains($path, '/responsive/complete')) {
            return $this->browse($request, Nag::VIEW_COMPLETE);
        }
        if (str_contains($path, '/responsive/future-incomplete')) {
            return $this->browse($request, Nag::VIEW_FUTURE_INCOMPLETE);
        }
        if (str_contains($path, '/responsive/future')) {
            return $this->browse($request, Nag::VIEW_FUTURE);
        }
        if (str_contains($path, '/responsive/all')) {
            return $this->browse($request, Nag::VIEW_ALL);
        }

        // Default: browse view (all tasks)
        return $this->browse($request, Nag::VIEW_ALL);
    }

    /**
     * Browse view - List all tasks grouped by task list
     *
     * @param ServerRequestInterface $request The request
     * @param int $filter Filter constant (Nag::VIEW_*)
     */
    protected function browse(ServerRequestInterface $request, int $filter = Nag::VIEW_ALL): ResponseInterface
    {
        global $registry, $prefs, $injector;

        // Get task lists to display
        $taskLists = Nag::listTasklists();
        $tasklistIds = array_keys($taskLists);

        // Fetch all tasks from visible task lists
        $options = [
            'include_history' => false,
            'tasklists' => $tasklistIds,  // Explicitly pass all visible tasklists
        ];

        // Apply filter
        switch ($filter) {
            case Nag::VIEW_INCOMPLETE:
                $options['completed'] = Nag::VIEW_INCOMPLETE;
                break;
            case Nag::VIEW_COMPLETE:
                $options['completed'] = Nag::VIEW_COMPLETE;
                break;
            case Nag::VIEW_FUTURE:
                $options['completed'] = Nag::VIEW_FUTURE;
                break;
            case Nag::VIEW_FUTURE_INCOMPLETE:
                $options['completed'] = Nag::VIEW_FUTURE_INCOMPLETE;
                break;
            case Nag::VIEW_ALL:
                $options['completed'] = Nag::VIEW_ALL;
                break;
            default:
                // Default to VIEW_ALL
                $options['completed'] = Nag::VIEW_ALL;
                break;
        }

        $tasks = Nag::listTasks($options);

        // Group tasks by task list
        $groupedTasks = [];
        // $taskLists already fetched above for filtering

        // Initialize groups
        foreach ($taskLists as $id => $taskList) {
            $groupedTasks[$id] = [
                'name' => $taskList->get('name'),
                'count' => 0,
                'tasks' => [],
            ];
        }

        // Group tasks
        $tasks->reset();
        while ($task = $tasks->each()) {
            $tasklistId = $task->tasklist;
            if (!isset($groupedTasks[$tasklistId])) {
                continue;
            }

            $groupedTasks[$tasklistId]['tasks'][] = $this->buildTaskData($task);
            $groupedTasks[$tasklistId]['count']++;
        }

        // Keep all task lists, including empty ones (removed filter)
        // Users should see all their task lists even if empty

        // Prepare view data
        $viewData = [
            'groupedTasks' => $groupedTasks,
            'filter' => $filter,
            'taskLists' => $taskLists,
            'totalTasks' => $tasks->count(),
        ];

        return $this->renderTemplate('browse.html.php', $viewData);
    }

    /**
     * Task detail view
     */
    protected function task(ServerRequestInterface $request, string $tasklistId, string $taskId): ResponseInterface
    {
        global $registry, $nag_shares;

        try {
            $task = Nag::getTask($tasklistId, $taskId);
        } catch (Nag_Exception $e) {
            return $this->redirectToBrowse('Task not found: ' . $e->getMessage());
        }

        // Check permissions
        try {
            $share = $nag_shares->getShare($tasklistId);
        } catch (Horde_Share_Exception $e) {
            return $this->redirectToBrowse('Access denied: ' . $e->getMessage());
        }

        $canEdit = $share->hasPermission($registry->getAuth(), Horde_Perms::EDIT);
        $canDelete = $share->hasPermission($registry->getAuth(), Horde_Perms::DELETE);

        $viewData = [
            'task' => $this->buildTaskData($task, true),
            'tasklistName' => $share->get('name'),
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ];

        return $this->renderTemplate('task.html.php', $viewData);
    }

    /**
     * Add task form
     */
    protected function add(ServerRequestInterface $request): ResponseInterface
    {
        global $registry;

        // Get available task lists
        $taskLists = Nag::listTasklists(false, Horde_Perms::EDIT);

        // Get default task list
        $defaultTasklist = Nag::getDefaultTasklist(Horde_Perms::EDIT);

        $viewData = [
            'taskLists' => $taskLists,
            'defaultTasklist' => $defaultTasklist,
            'priorities' => $this->getPriorities(),
        ];

        return $this->renderTemplate('add.html.php', $viewData);
    }

    /**
     * Create new task (POST handler)
     */
    protected function create(ServerRequestInterface $request): ResponseInterface
    {
        global $registry, $injector, $notification;

        $postData = $request->getParsedBody();

        // Validate required fields
        if (empty($postData['name'])) {
            $notification->push(_("Task name is required"), 'horde.error');
            return $this->redirectToAdd();
        }

        $tasklistId = $postData['tasklist'] ?? Nag::getDefaultTasklist(Horde_Perms::EDIT);

        // Build task array
        $task = [
            'name' => $postData['name'],
            'desc' => $postData['description'] ?? '',
            'priority' => (int) ($postData['priority'] ?? 3),
            'owner' => $registry->getAuth(),
            'private' => !empty($postData['private']),
        ];

        // Handle due date
        if (!empty($postData['due'])) {
            try {
                $date = new Horde_Date($postData['due']);
                $task['due'] = $date->timestamp();
            } catch (Exception $e) {
                // Invalid date, skip it
            }
        }

        // Handle start date
        if (!empty($postData['start'])) {
            try {
                $date = new Horde_Date($postData['start']);
                $task['start'] = $date->timestamp();
            } catch (Exception $e) {
                // Invalid date, skip it
            }
        }

        // Handle estimate
        if (!empty($postData['estimate'])) {
            $task['estimate'] = (float) $postData['estimate'];
        }

        // Handle assignee
        if (!empty($postData['assignee'])) {
            $task['assignee'] = trim($postData['assignee']);
        }

        try {
            $storage = $injector->getInstance('Nag_Factory_Driver')->create($tasklistId);
            $taskIds = $storage->add($task);

            $notification->push(sprintf(_("Created task: %s"), $task['name']), 'horde.success');

            // Redirect to task detail
            return $this->redirectToTask($taskIds[0], $tasklistId);
        } catch (Nag_Exception $e) {
            $notification->push(sprintf(_("Error creating task: %s"), $e->getMessage()), 'horde.error');
            return $this->redirectToAdd();
        }
    }

    /**
     * Edit task form
     */
    protected function edit(ServerRequestInterface $request, string $tasklistId, string $taskId): ResponseInterface
    {
        global $registry, $nag_shares;

        try {
            $task = Nag::getTask($tasklistId, $taskId);
            $share = $nag_shares->getShare($tasklistId);
        } catch (Exception $e) {
            return $this->redirectToBrowse('Error: ' . $e->getMessage());
        }

        // Check edit permission
        if (!$share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            return $this->redirectToBrowse('Access denied');
        }

        // Get available task lists (user might move task)
        $taskLists = Nag::listTasklists(false, Horde_Perms::EDIT);

        $viewData = [
            'task' => $this->buildTaskData($task, true),
            'taskLists' => $taskLists,
            'priorities' => $this->getPriorities(),
            'isEdit' => true,
        ];

        return $this->renderTemplate('add.html.php', $viewData);
    }

    /**
     * Update task (POST handler)
     */
    protected function update(ServerRequestInterface $request): ResponseInterface
    {
        global $registry, $injector, $notification, $nag_shares;

        $postData = $request->getParsedBody();

        $taskId = $postData['task_id'] ?? null;
        $tasklistId = $postData['original_tasklist'] ?? null;

        if (!$taskId || !$tasklistId) {
            $notification->push(_("Missing task ID or tasklist"), 'horde.error');
            return $this->redirectToBrowse();
        }

        try {
            $storage = $injector->getInstance('Nag_Factory_Driver')->create($tasklistId);
            $task = $storage->get($taskId);

            // Check permission
            $share = $nag_shares->getShare($tasklistId);
            if (!$share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                throw new Nag_Exception(_("Access denied"));
            }

            // Build update array
            $updates = [
                'name' => $postData['name'] ?? $task->name,
                'desc' => $postData['description'] ?? '',
                'priority' => (int) ($postData['priority'] ?? 3),
                'private' => !empty($postData['private']),
                'assignee' => trim($postData['assignee'] ?? ''),
            ];

            // Handle dates
            if (!empty($postData['due'])) {
                $date = new Horde_Date($postData['due']);
                $updates['due'] = $date->timestamp();
            }
            if (!empty($postData['start'])) {
                $date = new Horde_Date($postData['start']);
                $updates['start'] = $date->timestamp();
            }
            if (!empty($postData['estimate'])) {
                $updates['estimate'] = (float) $postData['estimate'];
            }

            // Merge and save
            $task->merge($updates);
            $task->save();

            $notification->push(sprintf(_("Updated task: %s"), $task->name), 'horde.success');
            return $this->redirectToTask($taskId, $tasklistId);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error updating task: %s"), $e->getMessage()), 'horde.error');
            return $this->redirectToBrowse();
        }
    }

    /**
     * Delete task
     */
    protected function delete(ServerRequestInterface $request): ResponseInterface
    {
        global $registry, $injector, $notification, $nag_shares;

        $params = $request->getQueryParams();
        $taskId = $params['id'] ?? null;
        $tasklistId = $params['tasklist'] ?? null;

        if (!$taskId || !$tasklistId) {
            $notification->push(_("Missing task ID or tasklist"), 'horde.error');
            return $this->redirectToBrowse();
        }

        try {
            $storage = $injector->getInstance('Nag_Factory_Driver')->create($tasklistId);
            $task = $storage->get($taskId);

            // Check permission
            $share = $nag_shares->getShare($tasklistId);
            if (!$share->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
                throw new Nag_Exception(_("Access denied"));
            }

            $taskName = $task->name;
            $storage->delete($taskId);

            $notification->push(sprintf(_("Deleted task: %s"), $taskName), 'horde.success');
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error deleting task: %s"), $e->getMessage()), 'horde.error');
        }

        return $this->redirectToBrowse();
    }

    /**
     * Toggle task completion
     */
    protected function complete(ServerRequestInterface $request): ResponseInterface
    {
        global $notification;

        $params = $request->getQueryParams();
        $taskId = $params['id'] ?? null;
        $tasklistId = $params['tasklist'] ?? null;

        if (!$taskId || !$tasklistId) {
            $notification->push(_("Missing task ID or tasklist"), 'horde.error');
            return $this->redirectToBrowse();
        }

        try {
            $completeTask = new Nag_CompleteTask();
            $result = $completeTask->result($taskId, $tasklistId);

            if ($result['data'] === 'complete') {
                $notification->push(_("Task marked as complete"), 'horde.success');
            } else {
                $notification->push(_("Task marked as incomplete"), 'horde.success');
            }
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error: %s"), $e->getMessage()), 'horde.error');
        }

        return $this->redirectToBrowse();
    }

    /**
     * Build task data array for templates
     */
    protected function buildTaskData($task, bool $includeAll = false): array
    {
        $data = [
            'id' => $task->id,
            'tasklist' => $task->tasklist,
            'name' => $task->name,
            'completed' => $task->completed,
            'priority' => $task->priority,
        ];

        // Due date
        if ($task->due) {
            $dueDate = new Horde_Date($task->due);
            $data['due'] = $dueDate->format('Y-m-d');
            $data['dueFormatted'] = $dueDate->format('M j, Y');

            // Check if overdue
            $now = new Horde_Date(time());
            $data['overdue'] = !$task->completed && $dueDate->compareDate($now) < 0;
        }

        if ($includeAll) {
            $data['description'] = $task->desc ?? '';
            $data['private'] = $task->private ?? false;

            if ($task->start) {
                $startDate = new Horde_Date($task->start);
                $data['start'] = $startDate->format('Y-m-d');
                $data['startFormatted'] = $startDate->format('M j, Y');
            }

            if ($task->estimate) {
                $data['estimate'] = $task->estimate;
            }

            $data['owner'] = $task->owner ?? '';
            $data['assignee'] = $task->assignee ?? '';
            $data['assigneeFormatted'] = $task->assignee ? Nag::formatAssignee($task->assignee, true) : '';
            $data['organizer'] = $task->organizer ?? '';
            $data['organizerFormatted'] = $task->organizer ? Nag::formatOrganizer($task->organizer, true) : '';
        }

        return $data;
    }

    /**
     * Get priority labels
     */
    protected function getPriorities(): array
    {
        return [
            1 => _("Highest"),
            2 => _("High"),
            3 => _("Normal"),
            4 => _("Low"),
            5 => _("Lowest"),
        ];
    }

    /**
     * Redirect helpers
     */
    protected function redirectToBrowse(string $message = ''): ResponseInterface
    {
        return $this->redirectTo(
            (string) Horde::url('responsive', true),
            $message
        );
    }

    protected function redirectToTask(string $taskId, string $tasklistId): ResponseInterface
    {
        return $this->redirectTo(
            (string) Horde::url('responsive/task/' . $tasklistId . '/' . $taskId, true)
        );
    }

    protected function redirectToAdd(): ResponseInterface
    {
        return $this->redirectTo(
            (string) Horde::url('responsive/add', true)
        );
    }
}
