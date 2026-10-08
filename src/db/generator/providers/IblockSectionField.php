<?php

namespace Bim\Db\Generator\Providers;

use Bim\Db\Generator\Code;
use Bim\Exception\BimException;
use CIBlock;
use CUserTypeEntity;

/**
 * Класс генерации кода пользовательских полей разделов инфоблока
 *
 * Class IblockSectionField
 * @package Bim\Db\Generator\Providers
 */
class IblockSectionField extends Code
{
    /**
     * @var \CUserTypeEntity|null
     */
    private $userType = null;

    /**
     * @var \CIBlock|null
     */
    private $iblock = null;

    /**
     * IblockSectionField constructor.
     */
    public function __construct()
    {
        # Требует обязательного подключения модуля
        # Iblock

        \CModule::IncludeModule("iblock");

        $this->iblock = new CIBlock();
        $this->userType = new CUserTypeEntity();
    }

    /**
     * Генерация создания
     *
     * generateAddCode
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function generateAddCode($params)
    {
        $this->checkParams($params);
        $return = "";
        $iblockData = $this->ownerItemDbData['iblockData'];
        if ($fieldData = $this->ownerItemDbData['fieldData']) {
            $fieldId = $fieldData['ID'];
            unset($fieldData['ID']);
            unset($fieldData['ENTITY_ID']);

            # add iblock code to
            if (($fieldData['USER_TYPE_ID'] == "iblock_element" || $fieldData['USER_TYPE_ID'] == "iblock_section") && (isset($fieldData['SETTINGS']['IBLOCK_ID']))) {
                if (!empty($fieldData['SETTINGS']['IBLOCK_ID'])) {
                    $iblockId = $fieldData['SETTINGS']['IBLOCK_ID'];
                    unset($fieldData['SETTINGS']['IBLOCK_ID']);
                    $rsIBlock = $this->iblock->GetList(array(), array('ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'));
                    if ($arIBlock = $rsIBlock->Fetch()) {
                        $fieldData['SETTINGS']['IBLOCK_CODE'] = $arIBlock['CODE'];
                    } else {
                        throw new BimException(' Not found iblock with id ' . $iblockId);
                    }
                }
            }

            # enumeration values
            if ($fieldData['USER_TYPE_ID'] == "enumeration") {
                $enumValues = array();
                $userFieldEnum = new \CUserFieldEnum();
                $dbEnum = $userFieldEnum->GetList(array('SORT' => 'ASC', 'ID' => 'ASC'), array('USER_FIELD_ID' => $fieldId));
                while ($enumData = $dbEnum->Fetch()) {
                    $enumValues[] = array(
                        'VALUE' => $enumData['VALUE'],
                        'DEF' => $enumData['DEF'],
                        'SORT' => $enumData['SORT'],
                        'XML_ID' => $enumData['XML_ID'],
                    );
                }
                if (!empty($enumValues)) {
                    $fieldData['ENUM_VALUES'] = $enumValues;
                }
            }

            $return = $this->getMethodContent('Bim\Db\Iblock\IblockSectionFieldIntegrate', 'Add',
                array($iblockData['CODE'], $fieldData));
        }
        return $return;
    }

    /**
     *  Генерация кода обновления
     *
     * generateUpdateCode
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function generateUpdateCode($params)
    {
        // UPDATE
    }

    /**
     * метод для генерации кода удаления
     *
     * generateDeleteCode
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function generateDeleteCode($params)
    {
        $this->checkParams($params);
        $return = "";
        $iblockData = $this->ownerItemDbData['iblockData'];
        if ($fieldData = $this->ownerItemDbData['fieldData']) {
            $return = $this->getMethodContent('Bim\Db\Iblock\IblockSectionFieldIntegrate', 'Delete',
                array($iblockData['CODE'], $fieldData['FIELD_NAME']));
        }
        return $return;
    }

    /**
     * Абстрактный метод проверки передаваемых параметров
     *
     * checkParams
     * @param array $params
     * @return mixed|void
     * @throws \Exception
     */
    public function checkParams($params)
    {
        if (!isset($params['iblockCode']) || !strlen($params['iblockCode'])) {
            throw new BimException('В параметрах не найден iblockCode');
        }
        if (!isset($params['fieldName']) || !strlen($params['fieldName'])) {
            throw new BimException('В параметрах не найден fieldName');
        }
        $rsIBlock = $this->iblock->GetList(array(), array('CODE' => $params['iblockCode'], 'CHECK_PERMISSIONS' => 'N'));
        if (!$iblockData = $rsIBlock->Fetch()) {
            throw new BimException('В системе не найден инфоблок с кодом = ' . $params['iblockCode']);
        }
        $this->ownerItemDbData['iblockData'] = $iblockData;

        $dbUserField = $this->userType->GetList(array(), array(
            'ENTITY_ID' => 'IBLOCK_' . $iblockData['ID'] . '_SECTION',
            'FIELD_NAME' => $params['fieldName'],
        ));
        if (!$userFieldRow = $dbUserField->Fetch()) {
            throw new BimException('Не найдено пользовательское поле раздела ' . $params['fieldName'] . ' в инфоблоке ' . $params['iblockCode']);
        }
        $this->ownerItemDbData['fieldData'] = $this->userType->GetByID($userFieldRow['ID']);
    }

}
