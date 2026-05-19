<?php echo _("We would like to remind you of this due task.") ?>


<?php echo $this->task->name ?>


<?php echo _("Date:") ?> <?php echo $this->due->format($this->dateFormat, new Horde\Date\Formatter\IcuFormatter(), $GLOBALS['language'] ?? 'en_US') ?>

<?php echo _("Time:") ?> <?php echo $this->due->format($this->timeFormat, new Horde\Date\Formatter\IcuFormatter(), $GLOBALS['language'] ?? 'en_US') ?>


<?php echo $this->task->desc ?>
