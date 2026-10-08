<?php

namespace Bim\Db\Main;

use Bim\Exception\BimException;

\CModule::IncludeModule("main");

/**
 * Class EventTypeIntegrate
 *
 * Documentation: http://cjp2600.github.io/bim-core/
 * @package Bim\Db\Main
 */
class EventTypeIntegrate
{
    /**
     * Добавление типа почтового события (для одного языка)
     *
     * Add
     * @param $fields
     * @return int
     * @throws BimException
     */
    public static function Add($fields)
    {
        global $APPLICATION;

        $arReqFields = array('EVENT_NAME', 'LID');
        foreach ($arReqFields as $key) {
            if (empty($fields[$key])) {
                throw new BimException('Field ' . $key . ' is empty.');
            }
        }
        unset($fields['ID']);

        $dbEventType = \CEventType::GetList(array('EVENT_NAME' => $fields['EVENT_NAME'], 'LID' => $fields['LID']));
        if ($dbEventType && $dbEventType->Fetch()) {
            throw new BimException('Event type with EVENT_NAME = "' . $fields['EVENT_NAME'] . '" and LID = "' . $fields['LID'] . '" already exist.');
        }

        $ID = \CEventType::Add($fields);
        if ($ID) {
            return $ID;
        } else {
            $error = 'Add event type error!';
            if ($ex = $APPLICATION->GetException()) {
                $error = $ex->GetString();
            }
            throw new BimException($error);
        }
    }

    /**
     * Update
     * @param $eventName
     * @param $fields
     * @return bool
     */
    public static function Update($eventName, $fields)
    {
        return true;
    }

    /**
     * Удаление типа почтового события (все языки) вместе с его почтовыми шаблонами
     *
     * Delete
     * @param $eventName
     * @return bool
     * @throws BimException
     */
    public static function Delete($eventName)
    {
        if (empty($eventName)) {
            throw new BimException('Empty event name');
        }

        $dbEventType = \CEventType::GetList(array('EVENT_NAME' => $eventName));
        if (!$dbEventType || !$dbEventType->Fetch()) {
            throw new BimException('Event type with EVENT_NAME = "' . $eventName . '" not found');
        }

        EventMessageIntegrate::Delete($eventName);

        if (!\CEventType::Delete($eventName)) {
            throw new BimException('Delete event type error!');
        }
        return true;
    }

}
