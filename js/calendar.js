/**
 * calendar.js - Calendar related javascript.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Nag
 */

var NagCalendar =
{
    calendarSelect: function(e)
    {
        var prefix, radio;

        switch (e.target.id) {
        case 'dueimg':
            prefix = 'due';
            radio = 'due_type_specified';
            break;

        case 'recur_endimg':
            prefix = 'recur_end';
            radio = 'recur_end_specified';
            break;

        case 'startimg':
            prefix = 'start';
            radio = 'start_date_specified';
            break;

        default:
            return;
        }

        document.getElementById(prefix + '_date').value = e.detail.toString(Nag.conf.date_format);
        document.getElementById(radio).value = 1;

        this.updateWday(prefix);
    },

    updateWday: function(p)
    {
        var d = this.getFormDate(p);
        if (d) {
            document.getElementById(p + '_wday').textContent = '(' + Horde_Calendar.fullweekdays[d.getDay()] + ')';
        }
    },

    getFormDate: function(p)
    {
        return Date.parseExact(document.getElementById(p + '_date').value, Nag.conf.date_format);
    },

    clickHandler: function(e)
    {
        if (e.button === 2) {
            return;
        }

        var elt = e.target,
            id = elt.id;

        switch (id) {
        case 'dueimg':
        case 'startimg':
        case 'recur_endimg':
            Horde_Calendar.open(elt, this.getFormDate(id.slice(0, -3)));
            e.preventDefault();
            break;

        case 'due_am_pm_am':
        case 'due_am_pm_am_label':
        case 'due_am_pm_pm':
        case 'due_am_pm_pm_label':
            document.getElementById('due_type_specified').value = 1;
            break;
        }
    },

    changeHandler: function(e)
    {
        switch (e.target.id) {
        case 'due_date':
            this.updateWday('due');
            // Fall-through

        case 'due_time':
            document.getElementById('due_type_specified').value = 1;
            break;

        case 'start_date':
            this.updateWday('start');
            // Fall-through

        case 'start_time':
            document.getElementById('start_date_specified').value = 1;
            break;

        case 'alarm_unit':
        case 'alarm_value':
            document.getElementById('alarmon').value = 1;
            break;
        }
    },

    onDomLoad: function()
    {
        this.updateWday('due');
        this.updateWday('start');
        this.updateWday('recur_end');

        var form = document.getElementById('nag_form_task_active');
        form.addEventListener('click', this.clickHandler.bind(this));
        form.addEventListener('change', this.changeHandler.bind(this));
    }
};

document.addEventListener('DOMContentLoaded', NagCalendar.onDomLoad.bind(NagCalendar));
document.addEventListener('Horde_Calendar:select', NagCalendar.calendarSelect.bind(NagCalendar));
