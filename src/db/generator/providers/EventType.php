<?php

namespace Bim\Db\Generator\Providers;

use Bim\Db\Generator\Code;
use Bim\Exception\BimException;
use Bim\Util\Helper;


/**
 * Класс генерации кода почтовых событий
 * (тип почтового события + его почтовые шаблоны)
 *
 * Class EventType
 * @package Bim\Db\Generator\Providers
 */
class EventType extends Code
{
    /**
     * Генерация создания
     *
     * generateAddCode
     * @param string $eventName
     * @return string
     * @throws \Exception
     */
    public function generateAddCode($eventName)
    {
        $this->checkParams($eventName);

        $return = array();
        foreach ($this->ownerItemDbData as $eventTypeData) {
            unset($eventTypeData['ID']);
            foreach ($eventTypeData as $k => $v) {
                if (is_null($v)) {
                    unset($eventTypeData[$k]);
                }
            }
            $return[] = $this->getMethodContent('Bim\Db\Main\EventTypeIntegrate', 'Add', array($eventTypeData));
        }

        $eventMessage = new \CEventMessage();
        $dbEventMessage = $eventMessage->GetList($by = "id", $order = "asc", array('TYPE_ID' => $eventName));
        while ($eventMessageData = $dbEventMessage->Fetch()) {

            # сайты шаблона (в GetList возвращается только один)
            $sites = array();
            $dbSite = $eventMessage->GetSite($eventMessageData['ID']);
            while ($siteData = $dbSite->Fetch()) {
                $sites[] = $siteData['LID'];
            }
            if (!empty($sites)) {
                $eventMessageData['LID'] = $sites;
            }

            Helper::unsetFields(array(
                'ID',
                'TIMESTAMP_X',
                'MESSAGE_PHP',
                'SITE_ID',
                'EVENT_TYPE',
                'EVENT_MESSAGE_TYPE_ID',
                'EVENT_MESSAGE_TYPE_NAME',
                'EVENT_MESSAGE_TYPE_EVENT_NAME',
            ), $eventMessageData);

            foreach ($eventMessageData as $k => $v) {
                if (strstr($k, "~") || is_null($v)) {
                    unset($eventMessageData[$k]);
                }
            }
            $return[] = $this->getMethodContent('Bim\Db\Main\EventMessageIntegrate', 'Add', array($eventMessageData));
        }

        return implode(PHP_EOL, $return);
    }

    /**
     * Генерация кода обновления
     *
     * generateUpdateCode
     * @param array $params
     * @return mixed|void
     */
    public function generateUpdateCode($params)
    {
        // Update generate
    }

    /**
     * Метод для генерации кода удаления
     *
     * generateDeleteCode
     * @param string $eventName
     * @return string
     * @throws \Exception
     */
    public function generateDeleteCode($eventName)
    {
        $this->checkParams($eventName);

        return $this->getMethodContent('Bim\Db\Main\EventTypeIntegrate', 'Delete', array($eventName));
    }

    /**
     * Абстрактный метод проверки передаваемых параметров
     *
     * checkParams
     * @param string $eventName
     * @return mixed|void
     * @throws \Exception
     */
    public function checkParams($eventName)
    {
        if (!isset($eventName) || !strlen($eventName)) {
            throw new BimException('empty eventName param');
        }
        $this->ownerItemDbData = array();
        $dbEventType = \CEventType::GetList(array('EVENT_NAME' => $eventName), array('ID' => 'asc'));
        while ($eventTypeData = $dbEventType->Fetch()) {
            $this->ownerItemDbData[] = $eventTypeData;
        }
        if (empty($this->ownerItemDbData)) {
            throw new BimException('Event type with EVENT_NAME = ' . $eventName . ' not exists');
        }
    }

}
