<?php


namespace Microservices\Responses;


use Microservices\Answers\Answer;
use Microservices\DataObjects\ArrayObject;


/**
 * Описывает класс ответа на запрос
 */
class Response extends ArrayObject
{
    /**
     * Массив ответов методов из запрсоов
     * @var array[]
     */
    public $response;

    /**
     * Создаёт ответ по входным данным, если они есть
     * @param Answer[] $answers
     */
    public function __construct(&$answers = [])
    {
        $this->response = ['responses' => []];
        if (!empty($answers)) {
            $this->create($answers);
        }

    }

    /**
     * Создаёт тело ответа на запрос из входных ответов методов
     * @param Answer[] $answers
     */
    public function create(&$answers)
    {
        foreach ($answers as $answer) {
            if (is_object($answer)) {
                $fields = $answer->getFields();
                if (empty($fields)) $fields = [];
                $this->response['responses'][] = $fields;
            } else {
                $this->response['responses'][] = (new Answer())->genError('Error: answer didn`t create');
            }

        }
    }

    /**
     * Пытается создать json ответ и возвращает его
     * @return bool|string
     */
    public function getJSON()
    {
        $json = json_encode($this->response);

        if (is_string($json)) return $json;

        return false;
    }
}