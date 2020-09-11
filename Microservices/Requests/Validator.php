<?php


namespace Microservices\Requests;


use Validators\BasicValidator;

class Validator extends BasicValidator
{
    public static function getRules()
    {
        return [
            'default' => [
                'method' => ['!empty'],
                'service,entity,method,version' => [['lmax' => 32]],
                'params' => [['cmax' => 32]],
                'requests' => [['cmax' => 16]],
                'key' => [['lmax' => 64]],
                'cookies' => [['cmax' => 16]],
            ]
        ];
    }
}