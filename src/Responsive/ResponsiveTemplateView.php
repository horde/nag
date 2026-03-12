<?php
/**
 * Nag Responsive Template View
 *
 * Simple template renderer for responsive views.
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */

declare(strict_types=1);

namespace Horde\Nag\Responsive;

/**
 * Responsive Template View
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class ResponsiveTemplateView
{
    /**
     * Template file path
     */
    protected string $templatePath;

    /**
     * View data
     */
    protected array $data;

    /**
     * Constructor
     */
    public function __construct(string $templatePath, array $data = [])
    {
        $this->templatePath = $templatePath;
        $this->data = $data;
    }

    /**
     * Render the template
     */
    public function render(): string
    {
        // Extract data to variables
        extract($this->data, EXTR_SKIP);

        // Helper function for escaping
        $escape = function($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        // Start output buffering
        ob_start();

        // Include template
        require $this->templatePath;

        // Return buffered content
        return ob_get_clean();
    }
}
