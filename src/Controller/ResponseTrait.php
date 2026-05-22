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
use Horde\Horde\Traits\HtmlResponseTrait;
use Horde\Horde\Traits\RedirectResponseTrait;

/**
 * Shared response helpers for Nag PSR-15 controllers.
 *
 * Expects the using class to have properties:
 *   - Horde_Notification_Handler $notification
 *   - Horde_PageOutput           $pageOutput
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
trait ResponseTrait
{
    use HtmlResponseTrait;
    use RedirectResponseTrait;

    /**
     * Render page content inside the Horde chrome (topbar, header, footer).
     *
     * The callable $renderBody is expected to echo its output.
     */
    private function renderChrome(string $title, callable $renderBody): string
    {
        // Use Horde's buffer tracking so PageOutput::header() doesn't call
        // flush() in PSR-15 responses before ResponseWriterWeb writes headers.
        Horde::startBuffer();
        $this->pageOutput->header(['title' => $title]);
        $this->notification->notify(['listeners' => 'status']);
        $renderBody();
        $this->pageOutput->footer();

        return Horde::endBuffer();
    }
}
