<?php

/**
 * ActiveSync recurring task completion tests.
 *
 * @author    Torben Dannhauer <torben@dannhauer.de>
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2026 The Horde Project (http://www.horde.org/)
 * @package   Nag
 */

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class Nag_Unit_Task_ActiveSyncRecurrenceTest extends TestCase
{
    protected function setUp(): void
    {
        $registry = $this->createMock(Horde_Registry::class);
        $registry->method('getAuth')->willReturn('alice@example.com');
        $GLOBALS['registry'] = $registry;

        $prefs = $this->createMock(Horde_Prefs::class);
        $prefs->method('getValue')->willReturn('tasklist1');
        $GLOBALS['prefs'] = $prefs;
    }

    public function testFromASTaskIgnoresMalformedAirSyncBaseBody()
    {
        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => Horde_ActiveSync::VERSION_TWELVE]
        );
        $message->subject = 'Body Test';
        $message->airsyncbasebody = '';

        $task = new Nag_Task();
        $task->fromASTask($message);

        $this->assertSame('', $task->desc);
        $this->assertSame('Body Test', $task->name);
    }

    public function testFromASTaskCompletesSingleInstanceWithDeadOccur()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 5);
        $existing->due = strtotime('2026-06-24 12:00:00');

        $message = $this->_createMessage([
            'complete' => true,
            'deadoccur' => true,
            'due' => '2026-06-23',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue(
            $task->recurrence->hasCompletion(2026, 6, 23)
        );
        $this->assertFalse($task->completed);
        $this->assertEquals(
            strtotime('2026-06-24 12:00:00'),
            $task->due
        );
    }

    public function testFromASTaskCompletesSingleInstanceWithCompleteFlag()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 5);
        $existing->due = strtotime('2026-06-24 12:00:00');

        $message = $this->_createMessage([
            'complete' => true,
            'due' => '2026-06-23',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue(
            $task->recurrence->hasCompletion(2026, 6, 23)
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskDeadOccurDoesNotReplaceRRule()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 5);

        $message = $this->_createMessage([
            'complete' => true,
            'deadoccur' => true,
            'due' => '2026-06-23',
        ]);
        $recurrence = Horde_ActiveSync::messageFactory('TaskRecurrence');
        $recurrence->type = Horde_ActiveSync_Message_Recurrence::TYPE_WEEKLY;
        $message->recurrence = $recurrence;

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue(
            $existing->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_DAILY)
        );
        $this->assertTrue(
            $task->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_DAILY)
        );
    }

    public function testFromASTaskFinalInstanceSetsCompleted()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 1);

        $message = $this->_createMessage([
            'complete' => true,
            'due' => '2026-06-23',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue($task->completed);
        $this->assertTrue(
            $task->recurrence->hasCompletion(2026, 6, 23)
        );
    }

    public function testFromASTaskUncompleteRemovesCompletion()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 5);
        $existing->due = strtotime('2026-06-26 12:00:00');
        $existing->recurrence->addCompletion(2026, 6, 23);
        $existing->recurrence->addCompletion(2026, 6, 24);

        $message = $this->_createMessage([
            'complete' => false,
            'deadoccur' => true,
            'due' => '2026-06-23',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 6, 23)
        );
        $this->assertTrue(
            $task->recurrence->hasCompletion(2026, 6, 24)
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskUncompleteWithoutDeadOccurRollsBackDue()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(5);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);
        $recurrence->addCompletion(2026, 7, 29);
        $recurrence->addCompletion(2026, 9, 2);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-10-07 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series F';
        $existing->uid = 'series-f';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = $this->_createMessage([
            'complete' => false,
            'due' => '2026-09-02',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 9, 2)
        );
        $this->assertEquals(
            strtotime('2026-09-02'),
            strtotime(date('Y-m-d', $task->due))
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskUncompletePrefersIosDueDateOverUtcDueDate()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(5);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);
        $recurrence->addCompletion(2026, 7, 29);
        $recurrence->addCompletion(2026, 9, 2);
        $recurrence->addCompletion(2026, 10, 7);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-11-11 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series F';
        $existing->uid = 'series-f';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => Horde_ActiveSync::VERSION_TWELVE]
        );
        $message->subject = 'Series F';
        $message->complete = false;
        $message->utcduedate = new Horde_Date('2026-10-06T22:00:00.000Z');
        $message->duedate = new Horde_Date('2026-10-07T00:00:00.000Z');

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 10, 7)
        );
        $this->assertEquals(
            strtotime('2026-10-07'),
            strtotime(date('Y-m-d', $task->due))
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskUncompleteRollsBackMasterDueToReopenedInstance()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);
        $recurrence->addCompletion(2026, 7, 1);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-08 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series G';
        $existing->uid = 'series-g';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => Horde_ActiveSync::VERSION_TWELVE]
        );
        $message->subject = 'Series G';
        $message->complete = false;
        $message->utcduedate = new Horde_Date('2026-06-30T22:00:00.000Z');
        $message->duedate = new Horde_Date('2026-07-01T00:00:00.000Z');

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 7, 1)
        );
        $this->assertEquals(
            strtotime('2026-07-01'),
            strtotime(date('Y-m-d', $task->due))
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskUncompleteRollsBackDueBeforeAdvancedMasterDue()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);
        $recurrence->addCompletion(2026, 7, 1);
        $recurrence->addCompletion(2026, 7, 8);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-15 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series Test';
        $existing->uid = 'series-test';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => Horde_ActiveSync::VERSION_TWELVE]
        );
        $message->subject = 'Series Test';
        $message->complete = false;
        $message->utcduedate = new Horde_Date('2026-07-07T22:00:00.000Z');
        $message->duedate = new Horde_Date('2026-07-08T00:00:00.000Z');

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 7, 8)
        );
        $this->assertEquals(
            strtotime('2026-07-08'),
            strtotime(date('Y-m-d', $task->due))
        );
        $nextDue = $task->getNextDue();
        $this->assertNotNull($nextDue);
        $this->assertEquals(
            strtotime('2026-07-08'),
            strtotime(date('Y-m-d', $nextDue->timestamp()))
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskUncompleteRollsBackWhenMasterDueEqualsOccurrence()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);
        $recurrence->addCompletion(2026, 7, 1);
        $recurrence->addCompletion(2026, 7, 8);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-08 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series G';
        $existing->uid = 'series-g-equal-due';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => Horde_ActiveSync::VERSION_TWELVE]
        );
        $message->subject = 'Series G';
        $message->complete = false;
        $message->utcduedate = new Horde_Date('2026-07-07T22:00:00.000Z');
        $message->duedate = new Horde_Date('2026-07-08T00:00:00.000Z');

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 7, 8)
        );
        $this->assertEquals(
            strtotime('2026-07-08'),
            strtotime(date('Y-m-d', $task->due))
        );
        $nextDue = $task->getNextDue();
        $this->assertNotNull($nextDue);
        $this->assertEquals(
            strtotime('2026-07-08'),
            strtotime(date('Y-m-d', $nextDue->timestamp()))
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskUncompleteRollsBackDueWithoutStoredCompletion()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-08 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series G';
        $existing->uid = 'series-g';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => Horde_ActiveSync::VERSION_TWELVE]
        );
        $message->subject = 'Series G';
        $message->complete = false;
        $message->utcduedate = new Horde_Date('2026-06-30T22:00:00.000Z');
        $message->duedate = new Horde_Date('2026-07-01T00:00:00.000Z');

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 7, 1)
        );
        $this->assertEquals(
            strtotime('2026-07-01'),
            strtotime(date('Y-m-d', $task->due))
        );
        $this->assertFalse($task->completed);
    }

    public function testActiveSyncFindMatchingCompletionUsesUtcFallback()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->addCompletion(2026, 6, 30);

        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => Horde_ActiveSync::VERSION_TWELVE]
        );
        $message->utcduedate = new Horde_Date('2026-06-30T22:00:00.000Z');
        $message->duedate = new Horde_Date('2026-07-01T00:00:00.000Z');

        $match = Nag_Task::activeSyncFindMatchingCompletion(
            $recurrence,
            $message
        );

        $this->assertNotNull($match);
        $this->assertSame('20260630', sprintf(
            '%04d%02d%02d',
            (int) $match->year,
            (int) $match->month,
            (int) $match->mday
        ));
    }

    public function testFromASTaskOutlookDeadOccurComplete()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 5);
        $existing->due = strtotime('2026-06-24 12:00:00');

        $message = $this->_createMessage([
            'complete' => true,
            'due' => '2026-06-23',
        ]);
        $recurrence = Horde_ActiveSync::messageFactory('TaskRecurrence');
        $recurrence->deadoccur = true;
        $recurrence->type = Horde_ActiveSync_Message_Recurrence::TYPE_WEEKLY;
        $message->recurrence = $recurrence;

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue(
            $task->recurrence->hasCompletion(2026, 6, 23)
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskMasterDueAdvanceAddsCompletion()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 5);

        $message = $this->_createMessage([
            'complete' => false,
            'due' => '2026-06-24',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue(
            $task->recurrence->hasCompletion(2026, 6, 23)
        );
        $this->assertEquals(
            strtotime('2026-06-24 12:00:00'),
            $task->due
        );
        $this->assertFalse($task->completed);
    }

    public function testFromASTaskIgnoresEpochDueOnMasterModify()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 5);
        $existing->due = strtotime('2026-06-23 12:00:00');

        $message = $this->_createMessage([
            'complete' => false,
            'due' => '1970-01-01',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 6, 23)
        );
        $this->assertEquals(
            strtotime('2026-06-23 12:00:00'),
            $task->due
        );
        $this->assertFalse($task->completed);
    }

    public function testToASTaskRecoversExportDueFromRecurrenceStart()
    {
        $task = $this->_createWeeklySeries('2026-06-23', 5);
        $task->due = 2;

        $message = $task->toASTask(['protocolversion' => 2.5]);

        $this->assertEquals(
            strtotime('2026-06-23 12:00:00'),
            $message->utcduedate->timestamp()
        );
        $this->assertSame(
            strtotime('2026-06-23 12:00:00'),
            $message->recurrence->start->timestamp()
        );
    }

    public function testToggleCompleteAdvancesDueDate()
    {
        $task = $this->_createWeeklySeries('2026-06-23', 5);
        $task->due = strtotime('2026-06-23 12:00:00');

        $task->toggleComplete();

        $this->assertEquals(
            strtotime('2026-06-24 12:00:00'),
            $task->due
        );
        $this->assertFalse($task->completed);
    }

    public function testToASTaskMidSeriesExportsIncompleteWithNextDue()
    {
        $task = $this->_createWeeklySeries('2026-06-23', 5);
        $task->recurrence->addCompletion(2026, 6, 23);
        $task->recurrence->addCompletion(2026, 6, 24);

        $message = $task->toASTask(['protocolversion' => 2.5]);

        $this->assertEquals(
            Horde_ActiveSync_Message_Task::TASK_COMPLETE_FALSE,
            $message->complete
        );
        $this->assertEquals(
            strtotime('2026-06-25 12:00:00'),
            $message->utcduedate->timestamp()
        );
    }

    public function testToASTaskFinalInstanceExportsComplete()
    {
        $task = $this->_createWeeklySeries('2026-06-23', 1);
        $task->recurrence->addCompletion(2026, 6, 23);
        $task->completed = true;
        $task->completed_date = time();

        $message = $task->toASTask(['protocolversion' => 2.5]);

        $this->assertEquals(
            Horde_ActiveSync_Message_Task::TASK_COMPLETE_TRUE,
            $message->complete
        );
    }

    public function testGetRemainingOccurrenceCount()
    {
        $task = $this->_createWeeklySeries('2026-06-23', 5);
        $this->assertSame(5, $task->getRemainingOccurrenceCount());

        $task->recurrence->addCompletion(2026, 6, 23);
        $task->recurrence->addCompletion(2026, 6, 24);
        $this->assertSame(3, $task->getRemainingOccurrenceCount());
    }

    public function testToASTaskExportsRemainingOccurrenceCount()
    {
        $task = $this->_createWeeklySeries('2026-06-23', 5);
        $task->recurrence->addCompletion(2026, 6, 23);
        $task->recurrence->addCompletion(2026, 6, 24);

        $message = $task->toASTask(['protocolversion' => 2.5]);

        $this->assertSame(3, $message->recurrence->occurrences);
    }

    public function testFromASTaskIgnoresCompletionBeyondSeriesCount()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 1);
        $existing->due = strtotime('2026-06-23 12:00:00');
        $existing->recurrence->addCompletion(2026, 6, 23);
        $existing->completed = true;

        $message = $this->_createMessage([
            'complete' => true,
            'due' => '2026-06-30',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue($existing->completed);
        $this->assertEquals($existing->due, $task->due);
        $this->assertTrue($task->completed);
    }

    public function testFromASTaskFinalInstanceMarksSeriesComplete()
    {
        $existing = $this->_createWeeklySeries('2026-06-23', 1);
        $existing->due = strtotime('2026-06-23 12:00:00');

        $message = $this->_createMessage([
            'complete' => true,
            'due' => '2026-06-23',
        ]);

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue($task->completed);
        $this->assertSame(0, $task->getRemainingOccurrenceCount());
    }

    public function testFromASTaskSeriesIMasterModifyThenCompleteThenUncomplete()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-01 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series I';
        $existing->uid = 'series-i';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $masterMessage = $this->_createMessage([
            'complete' => false,
            'due' => '2026-07-08',
            'recurrence' => true,
            'occurrences' => 3,
            'weekly' => true,
        ]);
        $masterMessage->subject = 'Series I';

        $afterMaster = new Nag_Task();
        $afterMaster->fromASTask($masterMessage, $existing);

        $this->assertFalse(
            $afterMaster->recurrence->hasCompletion(2026, 7, 1)
        );
        $this->assertEquals(
            strtotime('2026-07-08'),
            strtotime(date('Y-m-d', $afterMaster->due))
        );

        $completeMessage = $this->_createMessage([
            'complete' => true,
            'due' => '2026-07-01',
        ]);
        $completeMessage->subject = 'Series I';
        $completeMessage->utcduedate = new Horde_Date('2026-06-30T22:00:00.000Z');
        $completeMessage->duedate = new Horde_Date('2026-07-01T00:00:00.000Z');

        $afterComplete = new Nag_Task();
        $afterComplete->fromASTask($completeMessage, $afterMaster);

        $this->assertTrue(
            $afterComplete->recurrence->hasCompletion(2026, 7, 1)
        );
        $nextDue = $afterComplete->getNextDue();
        $this->assertNotNull($nextDue);
        $this->assertEquals(
            strtotime('2026-07-08'),
            strtotime(date('Y-m-d', $nextDue->timestamp()))
        );

        $uncompleteMessage = $this->_createMessage([
            'complete' => false,
            'due' => '2026-07-01',
        ]);
        $uncompleteMessage->subject = 'Series I';
        $uncompleteMessage->utcduedate = new Horde_Date('2026-06-30T22:00:00.000Z');
        $uncompleteMessage->duedate = new Horde_Date('2026-07-01T00:00:00.000Z');

        $afterUncomplete = new Nag_Task();
        $afterUncomplete->fromASTask($uncompleteMessage, $afterComplete);

        $this->assertFalse(
            $afterUncomplete->recurrence->hasCompletion(2026, 7, 1)
        );
        $this->assertEquals(
            strtotime('2026-07-01'),
            strtotime(date('Y-m-d', $afterUncomplete->due))
        );
        $reopenedDue = $afterUncomplete->getNextDue();
        $this->assertNotNull($reopenedDue);
        $this->assertEquals(
            strtotime('2026-07-01'),
            strtotime(date('Y-m-d', $reopenedDue->timestamp()))
        );
    }

    public function testFromASTaskMasterModifyWithRecurrencePreservesCompletions()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-01 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series I';
        $existing->uid = 'series-i';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = $this->_createMessage([
            'complete' => false,
            'due' => '2026-07-08',
            'recurrence' => true,
            'occurrences' => 3,
            'weekly' => true,
        ]);
        $message->subject = 'Series I';

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertTrue(
            $task->recurrence->hasCompletion(2026, 6, 24)
        );
        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 7, 1)
        );
        $this->assertEquals(
            strtotime('2026-07-08'),
            strtotime(date('Y-m-d', $task->due))
        );
    }

    public function testFromASTaskMasterModifyPreservesTotalSeriesCount()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);
        $recurrence->addCompletion(2026, 7, 1);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-08 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->name = 'Series I';
        $existing->uid = 'series-i-count';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        // iOS sends the *remaining* count (3) as POOMTASKS:Occurrences.
        $message = $this->_createMessage([
            'complete' => false,
            'due' => '2026-07-08',
            'recurrence' => true,
            'occurrences' => 3,
            'weekly' => true,
        ]);
        $message->subject = 'Series I';

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        // Total series count must remain 5, not be truncated to the remaining 3.
        $this->assertSame(5, $task->recurrence->getRecurCount());
        $this->assertSame(3, $task->getRemainingOccurrenceCount());

        // The full series must still be reachable through to the last instance.
        $task->recurrence->addCompletion(2026, 7, 8);
        $task->recurrence->addCompletion(2026, 7, 15);
        $this->assertSame(1, $task->getRemainingOccurrenceCount());
        $task->recurrence->addCompletion(2026, 7, 22);
        $this->assertSame(0, $task->getRemainingOccurrenceCount());
    }

    public function testFromASTaskUncompleteFinalInstanceViaMasterModify()
    {
        $due = strtotime('2026-06-24 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $recurrence->setRecurInterval(1);
        $recurrence->setRecurOnDay(Horde_Date::MASK_WEDNESDAY);
        $recurrence->setRecurCount(5);
        $recurrence->addCompletion(2026, 6, 24);
        $recurrence->addCompletion(2026, 7, 1);
        $recurrence->addCompletion(2026, 7, 8);
        $recurrence->addCompletion(2026, 7, 15);
        $recurrence->addCompletion(2026, 7, 22);

        $existing = new Nag_Task();
        $existing->due = strtotime('2026-07-22 12:00:00');
        $existing->recurrence = $recurrence;
        $existing->completed = true;
        $existing->completed_date = time();
        $existing->name = 'Series I';
        $existing->uid = 'series-i-final';
        $existing->tasklist = 'tasklist1';
        $existing->tags = [];

        $message = $this->_createMessage([
            'complete' => false,
            'due' => '2026-07-22',
            'recurrence' => true,
            'occurrences' => 1,
            'weekly' => true,
        ]);
        $message->subject = 'Series I';
        $message->utcduedate = new Horde_Date('2026-07-21T22:00:00.000Z');
        $message->duedate = new Horde_Date('2026-07-22T00:00:00.000Z');

        $task = new Nag_Task();
        $task->fromASTask($message, $existing);

        $this->assertFalse($task->completed);
        $this->assertNull($task->completed_date);
        $this->assertFalse(
            $task->recurrence->hasCompletion(2026, 7, 22)
        );
        $this->assertEquals(
            strtotime('2026-07-22'),
            strtotime(date('Y-m-d', $task->due))
        );
        $nextDue = $task->getNextDue();
        $this->assertNotNull($nextDue);
        $this->assertEquals(
            strtotime('2026-07-22'),
            strtotime(date('Y-m-d', $nextDue->timestamp()))
        );
    }

    public function testSeriesIsFullyComplete()
    {
        $task = $this->_createWeeklySeries('2026-06-23', 5);
        $this->assertFalse($task->seriesIsFullyComplete());

        $task->recurrence->addCompletion(2026, 6, 23);
        $this->assertFalse($task->seriesIsFullyComplete());

        $single = $this->_createWeeklySeries('2026-06-23', 1);
        $single->recurrence->addCompletion(2026, 6, 23);
        $single->completed = true;
        $this->assertTrue($single->seriesIsFullyComplete());
    }

    /**
     * @param string      $dueDate  YYYY-MM-DD
     * @param integer|null $count
     *
     * @return Nag_Task
     */
    protected function _createWeeklySeries($dueDate, $count = null)
    {
        $due = strtotime($dueDate . ' 12:00:00');
        $recurrence = new Horde_Date_Recurrence($due);
        $recurrence->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        if ($count !== null) {
            $recurrence->setRecurCount($count);
        }

        $task = new Nag_Task();
        $task->due = $due;
        $task->recurrence = $recurrence;
        $task->name = 'Series Test';
        $task->uid = 'series-uid';
        $task->tasklist = 'tasklist1';
        $task->tags = [];

        return $task;
    }

    /**
     * @param array $options
     *
     * @return Horde_ActiveSync_Message_Task
     */
    protected function _createMessage(array $options = [])
    {
        $message = new Horde_ActiveSync_Message_Task(
            ['protocolversion' => 2.5]
        );
        $message->subject = 'Series Test';
        $message->complete = !empty($options['complete']);

        if (!empty($options['deadoccur'])) {
            $message->deadoccur = true;
        }

        if (!empty($options['due'])) {
            $due = strtotime($options['due'] . ' 12:00:00');
            $message->utcduedate = new Horde_Date($due);
            $message->duedate = clone $message->utcduedate;
        }

        if (!empty($options['recurrence'])) {
            $recurrence = Horde_ActiveSync::messageFactory('TaskRecurrence');
            if (!empty($options['weekly'])) {
                $recurrence->type = Horde_ActiveSync_Message_Recurrence::TYPE_WEEKLY;
                $recurrence->dayofweek = Horde_Date::MASK_WEDNESDAY;
            } else {
                $recurrence->type = Horde_ActiveSync_Message_Recurrence::TYPE_DAILY;
            }
            if (!empty($options['occurrences'])) {
                $recurrence->occurrences = $options['occurrences'];
            }
            $message->recurrence = $recurrence;
        }

        return $message;
    }
}
