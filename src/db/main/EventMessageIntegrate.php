<?php

namespace Bim\Db\Main;

use Bim\Exception\BimException;

\CModule::IncludeModule("main");

/**
 * Class EventMessageIntegrate
 *
 * Documentation: http://cjp2600.github.io/bim-core/
 * @package Bim\Db\Main
 */
class EventMessageIntegrate
{
    /**
     * Добавление почтового шаблона
     *
     * Add
     * @param $fields
     * @return int
     * @throws BimException
     */
    public static function Add($fields)
    {
        $arReqFields = array('EVENT_NAME', 'LID');
        foreach ($arReqFields as $key) {
            if (empty($fields[$key])) {
                throw new BimException('Field ' . $key . ' is empty.');
            }
        }
        if (!isset($fields['ACTIVE']) || empty($fields['ACTIVE'])) {
            $fields['ACTIVE'] = "Y";
        }
        if (!isset($fields['EMAIL_FROM']) || empty($fields['EMAIL_FROM'])) {
            $fields['EMAIL_FROM'] = "#DEFAULT_EMAIL_FROM#";
        }
        if (!isset($fields['EMAIL_TO']) || empty($fields['EMAIL_TO'])) {
            $fields['EMAIL_TO'] = "#EMAIL_TO#";
        }
        if (!isset($fields['BODY_TYPE']) || empty($fields['BODY_TYPE'])) {
            $fields['BODY_TYPE'] = "text";
        }

        $eventMessage = new \CEventMessage();
        $ID = $eventMessage->Add($fields);
        if ($ID) {
            return $ID;
        } else {
            throw new BimException($eventMessage->LAST_ERROR);
        }
    }

    /**
     * Update
     * @param $ID
     * @param $fields
     * @return bool
     */
    public static function Update($ID, $fields)
    {
        return true;
    }

    /**
     * Удаление всех почтовых шаблонов типа почтового события
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

        $eventMessage = new \CEventMessage();
        $dbEventMessage = $eventMessage->GetList($by = "id", $order = "asc", array('TYPE_ID' => $eventName));
        while ($eventMessageData = $dbEventMessage->Fetch()) {
            if (!$eventMessage->Delete($eventMessageData['ID'])) {
                throw new BimException('Delete event message id = ' . $eventMessageData['ID'] . ' error!');
            }
        }
        return true;
    }

}
