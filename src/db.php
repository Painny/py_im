<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 17:05
 */

class DataBase{
    private $pdo;
    private $table;
    private $whereStr="";
    private $whereValue=[];
    private $field=" * ";
    private $order="";
    private $limit="";
    private $join="";
    private $sql="";
    private $sqlInfo;
    private $errInfo;

    public function __construct($dsn,$user,$pwd,$options=[])
    {
        $this->pdo=new PDO($dsn,$user,$pwd,$options);
    }

    public function table($table)
    {
        $this->table=$table;
        return $this;
    }

    public function where($whereStr,$value=[])
    {
        $this->whereStr=" WHERE ".$whereStr;
        $this->whereValue=$value;
        return $this;
    }

    public function field($field)
    {
        if(is_array($field)){
            $field=implode(",",$field);
        }
        $this->field=" ".$field." ";
        return $this;
    }

    public function order($field,$order="ASC")
    {
        $this->order=" ORDER BY ".$field." ".$order." ";
        return $this;
    }

    public function limit($param1,$param2=null)
    {
        $this->limit=" LIMIT ".$param1." ";
        if($param2){
            $this->limit.=",".$param2." ";
        }
        return $this;
    }

    public function join($table,$hostField,$condition,$joinField,$type="INNER")
    {
        $joinCondition=strpos($hostField,".")===false?$this->table.".".$hostField:$hostField;
        $joinCondition=$joinCondition.$condition.(strpos($joinField,".")===false?$table.".".$joinField:$joinField);
        $this->join.=" ".$type." JOIN ".$table." ON ".$joinCondition." ";
        return $this;
    }

    public function find()
    {
        $this->limit(1);
        $result=$this->get();
        if($result===false){
            return [];
        }
        return count($result)?$result[0]:[];
    }

    public function get()
    {
        $this->makeSql();
        $result=$this->execute();
        if($result===false){
            return [];
        }
        return $result;
    }

    public function count()
    {
        $this->field=" count(1) as count ";
        $this->makeSql();
        $result=$this->execute();
        if($result===false){
            return 0;
        }
        return $result[0]["count"];
    }

    public function insert($arr)
    {
        $fields=array_keys($arr);
        $values=array();
        foreach ($fields as $index => $key){
            $fields[$index]="`{$key}`";
            $values[$index]="?";
        }
        $sql="INSERT INTO ".$this->table."(".implode(",",$fields).") VALUES(".implode(",",$values).")";
        $this->sqlInfo["sql"]=$sql;
        $this->sqlInfo["bind"]=array_values($arr);
        $this->sql=$sql;
        $stm=$this->pdo->prepare($sql);
        foreach (array_values($arr) as $key => $val){
            $stm->bindValue($key+1,$val);
        }
        $stm->execute();
        $this->reset();
        return $stm->rowCount();
    }

    public function update($arr)
    {
        $update="";
        foreach (array_keys($arr) as $index => $value){
            if($index+1!=count(array_keys($arr))){
                $update.=$value."=?,";
            }else{
                $update.=$value."=?";
            }
        }
        $sql="UPDATE ".$this->table." SET ".$update.$this->whereStr;
        $this->sqlInfo["sql"]=$sql;
        $this->sqlInfo["bind"]=array_merge(array_values($arr),$this->whereValue);
        $this->sql=$sql;
        $stm=$this->pdo->prepare($sql);
        foreach ($this->sqlInfo["bind"] as $key => $val){
            $stm->bindValue($key+1,$val);
        }

        $stm->execute();
        $this->reset();
        return $stm->rowCount();
    }

    private function makeSql()
    {
        $sql="SELECT".$this->field."FROM ".$this->table.$this->join.$this->whereStr.$this->order.$this->limit;
        $this->sqlInfo["sql"]=$sql;
        $this->sqlInfo["bind"]=$this->whereValue;
        $this->sql=$sql;
        return $this;
    }

    private function execute()
    {
        $result=false;
        try{
            $stm=$this->pdo->prepare($this->sql);
            if($this->whereStr){
                foreach ($this->whereValue as $key => $val){
                    $stm->bindValue($key+1,$val);
                }
            }
            $stm->execute();
            $result=$stm->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $e){
            $this->errInfo["code"]=$e->getCode();
            $this->errInfo["info"]=$e->getMessage();
        }
        $this->reset();
        return $result;
    }

    public function getError()
    {
        return $this->errInfo;
    }

    public function lastSql()
    {
        return $this->sqlInfo;
    }

    private function reset()
    {
        $this->table=null;
        $this->whereStr="";
        $this->whereValue=[];
        $this->field=" * ";
        $this->order="";
        $this->limit="";
        $this->join="";
        $this->sql="";
    }

}