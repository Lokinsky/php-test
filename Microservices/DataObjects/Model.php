<?php


namespace Microservices\DataObjects;


use Medoo\Medoo;
use Microservices\Questions\Question;
use Plural\Inflect;
use ReflectionClass;
use Validators\BasicValidator;

class Model extends ArrayObject
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var Medoo
     */
    protected $db;

    public function create($fields=[]){
        
        $table = static::getTableName();
        $this->createdAt = time();
        if(empty($fields)){
            $fields = $this->getFields();
        }
        if($fields['id']==0) unset($fields['id']);
        
        $create = $this->db->insert($table,$fields);
        if($create->rowCount()>0) return $this->db->id();
        
        return false;
    }



    public function update($fields=[],$where=[]){
        $table = static::getTableName();
        if(empty($fields)){
            $fields = $this->getFields(true);
        }

        if(empty($where)){
            $where = ['id'=>$this->getId()];
        }

        $update = $this->db->update($table,$fields,$where);

        if($update->rowCount()>0) return true;

        return false;
    }



    public function find($where=[],$what='*'){
        $table = static::getTableName();

        if(empty($where)){
            $where = [
//                'id'=>$this->getId(),
                'LIMIT' => 32, // $model->createLimit($question)
            ];
        }

        $results = $this->db->select($table,$what,$where);

        return $results;
    }

    public function get($where=[],$what='*'){
        $table = static::getTableName();
        if(empty($where)){
            $where = ['id'=>$this->getId()];
        }


        return $this->db->get($table,$what,$where);
    }

    public function exists($where=[]){
        $table = static::getTableName();
        if(empty($where)){
            $where = ['id'=>$this->getId()];
        }
        
        return $this->db->has($table,$where);
    }

    public function delete($where=[]){
        $table = static::getTableName();
        if(empty($where)){
            $where = ['id'=>$this->getId()];
        }


        $delete = $this->db->delete($table,$where);
        if($delete->rowCount()>0) return true;


        return false;
    }

    /**
     * @param Question $question
     */
    public static function createLimit($question){
        $rules = [
            'offset' => [
                'offset' => ['int',['rmin'=>0]],
            ],
            'count' => [
                'count' => ['int',['rmin'=>0]],
            ]
        ];

        if(isset($question->offset) and BasicValidator::validate($question,'offset',$rules)){
            $offset = $question->offset;
        }else{
            $offset = 0;
        }

        if(isset($question->count) and BasicValidator::validate($question,'count',$rules)){
            $count = $question->count;
        }else{
            $count = 32;
        }

        return [$offset,$count];
    }

    /**
     * @return int
     */
    public function getId()
    {

        if(empty($this->id)) return 0;

        return $this->id;
    }

    public function setDb(&$db){
        $this->db = $db;
    }

    public static function getTableName(){
        $name = basename(static::class);

        return mb_strtolower(Inflect::pluralize($name));
    }

    public function getFields($clearNullFlag = false)
    {
        $publicFields = [];
        $reflector = new ReflectionClass(get_class($this));
        $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if($clearNullFlag and is_null($property->getValue($this))) continue;
            $publicFields[$property->getName()] = $property->getValue($this);
//            var_dump($property->getName());
//            var_dump($property->getValue($this));
        }


        return $publicFields;
    }
}