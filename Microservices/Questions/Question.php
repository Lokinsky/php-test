<?php


namespace Microservices\Questions;


use Microservices\DataObjects\ArrayObject;

/**
 * Описывает универсальный объект параметров для методов API
 */
class Question extends ArrayObject
{
    /**
     * Заполняет свои поля значениями вхходного массива.
     * @param array $from
     */
    public function __construct($from = [])
    {
        if (!is_array($from)) $from = [];

        $this->pull($from);
    }

    /**
     * Перегрузка метода, так как родительский почему-то не работал как надо
     *
     * @return array
     */
    public function getFields()
    {
        return get_object_vars($this);
    }

    /**
     * Позволяет обращаться к несуществующим свойствам
     *
     * @param string $name
     * @return |null
     */
    public function __get($name)
    {
        return null;
    }
}