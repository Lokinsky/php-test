<?php


namespace Microservices\DataObjects;

use ReflectionClass;

/**
 * Описывает класс массива-объекта, хранящего данные в своих полях
 */
class ArrayObject
{
    /**
     * Заполняет свои поля значениями входного массива.
     * Если заданы связи псевдонимов, то созраняет только найденные поля.
     *
     * @param array $fromArray
     */
    public function pull(&$fromArray)
    {
        $aliases = $this->getFieldsAliases();

        if (empty($aliases)) {
            $this->directPull($fromArray);
        } else {
            $this->aliasesPull($fromArray, $aliases);
        }
    }

    /**
     * Возвращает массив зависимостей для псведонимов.
     * Формат: ['fieldName'=>[aliases]]
     * @return array
     */
    public function getFieldsAliases()
    {
        return [];
    }

    /**
     * Наполняет поля объекта напрямую значениями массива
     * @param array $fromArray
     */
    public function directPull(&$fromArray)
    {
        foreach ($fromArray as $index => $value) {
            $this->$index = $value;
        }
    }

    /**
     * Заполняет поля объекта значениями из массива в соответствии с псевдонимами
     * @param array $fromArray
     * @param array|bool $fieldsAliases
     */
    public function aliasesPull(&$fromArray, $fieldsAliases = false)
    {
        if (!is_array($fieldsAliases)) $fieldsAliases = $this->getFieldsAliases();

        foreach ($fieldsAliases as $fieldName => $aliases) {
            if (is_string($aliases)) {
                if (isset($fromArray[$aliases])) {
                    $this->$fieldName = $fromArray[$aliases];
                }
            } else {
                foreach ($aliases as $alias) {
                    if (isset($fromArray[$alias])) {
                        $this->$fieldName = $fromArray[$alias];
                    }
                }
            }
        }
    }

    /**
     * Возвращает массив полей
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getFields()
    {
        $fieldsNames = $this->getFieldsNames();
        if (empty($fieldsNames)) {
            return $this->getDefinedPublicFields();
        }

        return $this->getCatchedFields();
    }

    /**
     * Возвращает массив с именами полей
     * @return array
     */
    public function getFieldsNames()
    {
        return [];
    }

    /**
     * Возвращает массив публичных полей объявленных в классе
     * @return array
     * @throws \ReflectionException
     */
    public function getDefinedPublicFields()
    {
        $publicFields = [];
        $reflector = new ReflectionClass(get_class($this));
        $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $publicFields[$property->getName()] = $property->getValue($this);
        }

        return $publicFields;
    }

    /**
     * Возвращает массив полей по их именам, если они найдены
     * @param array|bool $fieldsNames
     * @param bool $nullFlag
     * @return array
     */
    public function getCatchedFields($fieldsNames = false, $nullFlag = false)
    {
        if (!is_array($fieldsNames)) $fieldsNames = $this->getFieldsNames();
        $fields = [];
        foreach ($fieldsNames as $fieldName) {
            if (isset($this->$fieldName)) {
                $fields[$fieldName] = $this->$fieldName;
            } elseif ($nullFlag) {
                $fields[$fieldName] = null;
            }
        }

        return $fields;
    }

    /**
     * Возвращает массив всех объявленных полей
     * @return array
     */
    public function getExistsFields()
    {
        return get_object_vars($this);
    }


}