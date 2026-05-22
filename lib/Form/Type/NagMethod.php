<?php

/**
 * The Horde_Form_Type_nag_method class provides a form field for editing
 * notification methods for a task alarm.
 *
 * @author  Alfonso Marin <almarin@um.es>
 * @package Nag
 */
class Nag_Form_Type_NagMethod extends Horde_Form_Type
{
    public function getInfo($vars, $var, $info)
    {
        $info = $var->getValue($vars);
        if (!is_array($info) || empty($info['on'])) {
            $info = [];
            return $info;
        }

        $types = $vars->get('task_alarms');
        $info = [];
        if (!empty($types)) {
            foreach ($types as $type) {
                $info[$type] = [];
                switch ($type) {
                    case 'notify':
                        $info[$type]['sound'] = $vars->get('task_alarms_sound');
                        break;
                    case 'mail':
                        $info[$type]['email'] = $vars->get('task_alarms_email');
                        break;
                    case 'popup':
                        break;
                }
            }
        }
        return $info;
    }

    public function isValid($var, $vars, $value, $message)
    {
        $alarm = $vars->get('alarm');
        $alarmOn = is_array($alarm) ? !empty($alarm['on']) : !empty($alarm);
        if (is_array($value) && !empty($value['on']) && !$alarmOn) {
            $this->message = _("An alarm must be set to specify a notification method");
            return false;
        }
        return true;
    }

    public function getTypeName()
    {
        return 'NagMethod';
    }

}
