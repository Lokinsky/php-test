<?php


namespace Microservices;


use AuthenticationMicroservice\Authentication;
use DBProxy\DbProxy;
use Microservices\Answers\Answer;
use Microservices\Questions\Question;
use Microservices\Requests\Request;
use Microservices\Requests\Validator;
use Microservices\Responses\Response;

/**
 * Класс микросервисов
 *
 * Описывает минимальный функционал любого микросервиса
 */
class Microservice extends Singleton implements MicroserviceI
{
    /**
     * Объект для работы с базой данных
     *
     * @var \Medoo\Medoo экземпля Medoo
     */
    public $db;

    protected $currentRequest;

    protected $storage;

    /**
     * Microservice constructor.
     * Обеспечивает создание директории сервиса.
     * Создаёт подключение к базе данных
     */
    protected function __construct()
    {
        parent::__construct();
        self::provideServiceDir();
        DbProxy::createDataBaseConnection($this);
    }

    /**
     * Создаёт директорию сервиса, если её нет
     */
    public function provideServiceDir()
    {
        $path = $this->getServiceDirPath();

        if (!file_exists($path)) return mkdir($path, 0777, true);
        return true;
    }

    /**
     * Путь до рабочей папки сервиса
     *
     * @return string
     */
    public function getServiceDirPath()
    {
        return $this->getWorkDirPath() . '/' . $this->getServiceName();
    }

    /**
     * Возвращает название рабочей директории для сервисов
     *
     * @return string
     */
    public static function getWorkDirPath()
    {
        return 'services';
    }

    /**
     * Возвращает имя сервиса без наймспейса
     *
     * @return string
     */
    public function getServiceName()
    {
        return basename(get_class($this));
    }

    public function getCurrentRequest(){
        return $this->currentRequest;
    }

    protected function setCurrentRequest($request){
        $this->currentRequest = $request;
    }

    /**
     * Запускает сервис, обрабатывает запросы, выполняет их, возвращает общий ответ.
     * @return bool|string
     */
    public function run()
    {
        $request = new Request();

        $allRequests = $request->getAll();


        $answers = [];

        foreach ($allRequests as $request) {
            if (Validator::validate($request)) {
                $this->setCurrentRequest($request);
                $address = $this->addressRequest($request);
                $answers[] = $this->handleRequest($request, $address);
            } else {
                $answers[] = (new Answer())->genError('Error: failed validation');
            }
        }

        $response = new Response($answers);



        return $this->viewResponse($response,false);
    }

    /**
     * @param Response $response
     * @return string
     */
    public function viewResponse($response,$headers=true){
        if($headers) header('Content-Type: application/json');

        $json = $response->getJSON();

        if ($json !== false) {
            echo $json;
        }

        return $json;
    }

    /**
     * Ищет адрес сервиса для запроса
     *
     * @param Request $request
     * @return bool|string
     */
    public function addressRequest($request)
    {
        $map = $this->getServiceMap();
//        var_dump($request->service);
        if (empty($request->service)) $request->service = $this->getServiceName();
//        var_dump($map);
        foreach ($map as $address => $services) {
            if (in_array($request->service, $services) !== false) {
                return $address;
            }
        }

        return false;
    }

    /**
     * Карта сервисов.
     * Ассоциаиции адресов сервисов и их названий.
     *
     * @return string[][]
     */
    public function getServiceMap()
    {
        $serviceMapPath = $this->getServiceDirPath() . '/servicemap.php';
        if (file_exists($serviceMapPath)) {
            return include $serviceMapPath;
        }

        return [
            'local' => [
                $this->getServiceName()
            ]
        ];
    }

    /**
     * Выполняет запрос в зависмости от его адреса.
     *
     * @param Request $request
     * @param string $address
     * @return Answer
     */
    public function handleRequest($request, $address)
    {
        if (empty($address)) return (new Answer())->genError('Error: failed to find request address');

        if ($address == 'local') {
            return $this->executeLocalRequest($request);
        } else {
            return apiProxy::sendRequest($request, $address);
        }
    }

    /**
     * Выполняет локальный запрос
     *
     * @param Request $request
     * @return Answer
     */
    public function executeLocalRequest($request)
    {
        $question = new Question($request->params);

        $serviceName = $this->getServiceName();
        if (!$this->compareServiceNames($serviceName, $request->service)) {
            $namespace = $request->service . 'Microservice';

            $microservice = $namespace . '\\' . ($request->service);

            if (class_exists($microservice)) {
                $finalService = $microservice::getInstance();
                return $finalService->executeLocalRequest($request);
            } else {
                return (new Answer())->genError('Error: local service not found');
            }
        }

        if (empty($request->entity)) {
            $entity = $this;
        } else {
            $class = __NAMESPACE__ . '\\' . $request->entity;
            if (class_exists($class)) {
                $entity = new $class();
            }
        }

        $method = 'Api' . $request->method;

        if (!$this->checkAccess($request)) return (new Answer())->genError('Error: access denied');

        if (method_exists($entity, $method)) {
            return $entity->$method($question);
        }

        return (new Answer())->genError('Error: failed to execute local request');
    }

    /**
     * Сравнивает имена сервисов
     *
     * @param $name1
     * @param $name2
     * @return bool
     */
    public function compareServiceNames($name1, $name2)
    {
        return $name1 == $name2;
    }

    /**
     * Проверяет, есть ли доступ для выполнение запроса
     *
     * @param Request $request
     * @return bool
     */
    public function checkAccess($request)
    {
        if(isset($request->serviceKey)){
            if(apiProxy::validServiceKey($request->serviceKey)){
                return  true;
            }
        }

        $authAnswer = apiProxy::go($this)->Authentication->checkAccess($request->getFields());

        if($authAnswer->access){
            $this->storage['currentUser'] = $authAnswer->currentUser;
            return  true;
        }

        return  false;
    }
}