<?php

namespace Bim\Db\Iblock;

use Bim\Exception\BimException;

\CModule::IncludeModule("iblock");

/**
 * Class IblockSectionFieldIntegrate
 *
 * Documentation: http://cjp2600.github.io/bim-core/
 * @package Bim\Db\Iblock
 */
class IblockSectionFieldIntegrate
{
    /**
     * Добавление пользовательского поля разделов инфоблока
     *
     * Add
     * @param $iblockCode
     * @param $fields
     * @return mixed
     * @throws \Exception
     */
    public static function Add($iblockCode, $fields)
    {
        global $APPLICATION;

        if (empty($iblockCode) || empty($fields)) {
            throw new BimException('iblockCode or fields is empty');
        }
        if (empty($fields['FIELD_NAME'])) {
            throw new BimException('Field FIELD_NAME is required.');
        }
        if (empty($fields['USER_TYPE_ID'])) {
            throw new BimException('Field USER_TYPE_ID is required.');
        }
        if (isset($fields['ID'])) {
            unset($fields['ID']);
        }
        $fields['ENTITY_ID'] = self::_getEntityId($iblockCode);

        $typeEntityDbRes = \CUserTypeEntity::GetList(array(), array(
            "ENTITY_ID" => $fields["ENTITY_ID"],
            "FIELD_NAME" => $fields["FIELD_NAME"],
        ));
        if ($typeEntityDbRes !== false && $typeEntityDbRes->Fetch()) {
            throw new BimException('Iblock section field with name = "' . $fields["FIELD_NAME"] . '" already exist.');
        }

        #if
        if (($fields['USER_TYPE_ID'] == "iblock_element" || $fields['USER_TYPE_ID'] == "iblock_section") && (isset($fields['SETTINGS']['IBLOCK_CODE']))) {
            $linkIblockCode = $fields['SETTINGS']['IBLOCK_CODE'];
            unset($fields['SETTINGS']['IBLOCK_CODE']);
            $rsIBlock = \CIBlock::GetList(array(), array('CODE' => $linkIblockCode, 'CHECK_PERMISSIONS' => 'N'));
            if ($arIBlock = $rsIBlock->Fetch()) {
                $fields['SETTINGS']['IBLOCK_ID'] = $arIBlock['ID'];
            } else {
                throw new BimException(__METHOD__ . ' Not found iblock with code ' . $linkIblockCode);
            }
        }

        $enumValues = array();
        if (isset($fields['ENUM_VALUES'])) {
            $enumValues = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        $userType = new \CUserTypeEntity;
        $ID = $userType->Add($fields);
        if (!(int)$ID) {
            $error = 'Not added iblock section field';
            if ($ex = $APPLICATION->GetException()) {
                $error .= ': ' . $ex->GetString();
            }
            throw new BimException($error);
        }

        # enumeration values
        if (!empty($enumValues)) {
            $values = array();
            $i = 0;
            foreach ($enumValues as $enumValue) {
                $values['n' . $i] = $enumValue;
                $i++;
            }
            $userFieldEnum = new \CUserFieldEnum();
            if (!$userFieldEnum->SetEnumValues($ID, $values)) {
                throw new BimException('Not added enumeration values for iblock section field ' . $fields['FIELD_NAME']);
            }
        }
        return $ID;
    }

    /**
     * Удаление пользовательского поля разделов инфоблока
     *
     * Delete
     * @param $iblockCode
     * @param $fieldName
     * @return mixed
     * @throws \Exception
     */
    public static function Delete($iblockCode, $fieldName)
    {
        if (empty($iblockCode)) {
            throw new BimException('iblockCode is required');
        }
        if (empty($fieldName)) {
            throw new BimException('fieldName is required.');
        }

        $typeEntityDbRes = \CUserTypeEntity::GetList(array(), array(
            "ENTITY_ID" => self::_getEntityId($iblockCode),
            "FIELD_NAME" => $fieldName,
        ));
        if ($fieldData = $typeEntityDbRes->Fetch()) {
            $userType = new \CUserTypeEntity;
            if (!$userType->Delete($fieldData['ID'])) {
                throw new BimException('Not delete iblock section field');
            }
            return $fieldData['ID'];
        } else {
            throw new BimException('Not found iblock section field with name ' . $fieldName);
        }
    }

    /**
     * Получение ENTITY_ID пользовательских полей разделов инфоблока
     *
     * _getEntityId
     * @param $iblockCode
     * @return string
     * @throws \Exception
     */
    public static function _getEntityId($iblockCode)
    {
        $rsIBlock = \CIBlock::GetList(array(), array('CODE' => $iblockCode, 'CHECK_PERMISSIONS' => 'N'));
        if (!$arIBlock = $rsIBlock->Fetch()) {
            throw new BimException('Not found iblock with code = "' . $iblockCode . '"');
        }
        return sprintf('IBLOCK_%s_SECTION', $arIBlock['ID']);
    }

}
