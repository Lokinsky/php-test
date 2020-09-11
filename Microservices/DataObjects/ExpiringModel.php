<?php


namespace Microservices\DataObjects;


class ExpiringModel extends Model
{
    public $duration;
    public $createdAt;

    public function checkDuration(){
        if($this->duration==0) return true;

        if(!empty($this->createdAt)){
            $now = time();
            if(($this->createdAt+$this->duration)<$now) return true;
        }

        return false;
    }
}


