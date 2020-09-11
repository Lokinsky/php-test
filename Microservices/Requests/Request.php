<?php


namespace Microservices\Requests;

use Microservices\DataObjects\ArrayObject;

//use Validators\ValidatorVisitor;


/**
 * Описывает класс работы с входящими запросами.
 * Описывает возможные поля запроса, его структуру.
 */
class Request extends ArrayObject
{
    /**
     * @var string Название сервиса
     */
    public $service;

    /**
     * @var string Версия API
     */
    public $version;

    /**
     * @var string Ключ доступа к API
     */
    public $key;

    /**
     * @var string Ключ сервисного доступа к API
     */
    public $serviceKey;

    /**
     * @var string Название сущности сервиса
     */
    public $entity;

    /**
     * @var string Название метода API
     */
    public $method;

    /**
     * @var array Массив параметров метода
     */
    public $params;


    /**
     * @var array $cookies Массив куков, в случе неудачной передачи куков
     */
    public $cookies;

    /**
     * @var Request[] Массив запросов
     */
    protected $requests;


    /**
     * Заполняет свои поля либо из входного массива, либо парсингом
     * @param array $fields
     */
    public function __construct($fields = [])
    {
        if (empty($fields)) {
            $this->parse();
        } else {
            $this->pull($fields);
        }
    }


    /**
     * Парсит поля запроса
     */
    public function parse()
    {
        $this->parsePOST();
        $this->parseGET();
        $this->parseSERVER();
    }

    /**
     * Парсит POST
     */
    public function parsePOST()
    {
        $this->pull($_POST);
    }

    /**
     * Парсит GET
     */
    public function parseGET()
    {
        $this->pull($_GET);
    }

    /**
     * Парсит значения из SERVER
     */
    public function parseSERVER()
    {
        $this->parseURI();
    }

    /**
     * Парсит URI
     */
    public function parseURI()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', $uri);

        if (!empty($parts[0])) $this->service = $parts[0];
        if (!empty($parts[1])) $this->version = $parts[1];
        if (!empty($parts[2])) $this->entity = $parts[2];
        if (!empty($parts[3])) $this->method = $parts[3];
    }

    /**
     * Возвращает массив псевдонимов для полей
     * @return array|\string[][]
     */
    public function getFieldsAliases()
    {
        return [
            'service' => ['s', 'srv', 'serv', 'service'],
            'version' => ['v', 'ver', 'version'],
            'key' => ['k', 'key'],
            'serviceKey' => ['sk'],
            'entity' => ['e', 'ent', 'entity'],
            'method' => ['m', 'meth', 'method'],
            'params' => ['p', 'prms', 'params'],
            'cookies' => ['cookies', 'cks'],
            'requests' => ['r', 'rqs', 'reqs', 'requests'],
        ];
    }

//    /**
//     * Производит валидацию запроса посредством посетителя-валидатора
//     *
//     * @param ValidatorVisitor $validator
//     * @return bool
//     */
//    public function validate($validator){
//        return $validator->validateRequest($this);
//    }


    /**
     * Разделяет входящий запрос на массив подзапросов
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getAll()
    {
        $all = [];

        if (empty($this->requests) !== true) {
            $defRequestFields = $this->getFields();
            foreach ($this->requests as $request) {
                $all[] = new Request(array_merge($defRequestFields, $request));
            }
        } else {
            $all[] = $this;
        }

        return $all;
    }
}