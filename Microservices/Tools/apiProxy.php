<?php


namespace Microservices\Tools;


use GuzzleHttp\Client;
use Microservices\Answers\Answer;
use Microservices\Microservice;
use Microservices\Requests\Request;

class apiProxy
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Microservice
     */
    protected $parent;

//    protected $service;
//    protected $entity;
//    protected $method;
//    protected $arguments;

    public function __construct($parent=null)
    {
        $this->parent = $parent;
        $default = [
            'v' => 0.1,
            'cks' => $_COOKIE,
            'sk' => $this->generateServiceKey(),
        ];

        $this->request = new Request($default);
    }

    public static function generateServiceKey(){
        return 'hdsuihcdsilcashciewhi3!323eew^32ffg';
    }

    public static function validServiceKey($key){
        return $key == static::generateServiceKey();
    }

    public static function go($parent=null){
        return new self($parent);
    }

    public function __get($name)
    {
        if(empty($this->request->service)){
            $this->request->service = $name;
        }elseif (empty($this->request->entity)){
            $this->request->entity = $name;
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        if(isset($arguments[0])) $arguments = $arguments[0];

        $this->request->method = $name;
        $this->request->params = $arguments;

        if(!empty($this->parent)){
            $address = $this->parent->addressRequest($this->request);
            $answer = $this->parent->handleRequest($this->request,$address);

            return $answer;
        }

        return false;
    }

    /**
     * Пересылает запрос на указанный адрес.
     * Возвращает полученный ответ.
     *
     * @param Request $request
     * @param string $address
     * @return Answer
     */
    public static function sendRequest($request, $address)
    {
        $answer = new Answer();
        $client = new Client();

        $res = $client->request('POST', $address, [
            'form_params' => $request->getFields(),
        ]);

        if ($res->getStatusCode() != 200) {
            return $answer->genError('Error: remote service not available [' . $res->getStatusCode() . ']');
        }

        $contents = json_decode($res->getBody()->getContents(), true);

        if (isset($contents['responses'])) {
            $answer->pull($contents['responses']);
        } else {
            $answer->genError('Error: bad response from remote Service');
        }

        return $answer;
    }
}