<?php


namespace Microservices\Answers;


use Microservices\DataObjects\ArrayObject;


/**
 * Описывает класс ответа любых методов API
 */
class Answer extends ArrayObject
{
    /**
     * При необходимости заполняет свои свойства значениями входного массива
     * @param array $from
     */
    public function __construct($from = [])
    {
        $this->pull($from);
    }

    /**
     * Создаёт в свойствах массив строк для ошибок если его нет.
     * И добавляет переданную строку в массив ошибок.
     *
     * @param string $error
     * @return $this
     */
    public function genError($error)
    {
        if (!isset($this->errors)) $this->errors = [];
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Возвращает масссив всех доступных полей объекта
     * @return array
     */
    public function getFields()
    {
        return get_object_vars($this);
    }
}