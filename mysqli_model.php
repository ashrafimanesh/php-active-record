<?php

require_once __DIR__."/drivers/db_mysqli.php";
/**
 * Base methods for each model layers
 *
 * @author ashrafimanesh
 */
class mysqli_model{
    public static $tbl_name;
    public $db;
    public function __construct() {
        $database_config= require_once __DIR__.'/config.php';
        $this->db = db_mysqli::get_instance($database_config['mysqli']['database'],
                $database_config['mysqli']['host'],
                $database_config['mysqli']['user'],
                $database_config['mysqli']['pass']);
   }
    public static function get_tbl_name() {
        $class=get_called_class();
        return $class::$tbl_name;
    }
    
    public function get_rows($wheres=[]){
        if(sizeof($wheres)) {
            $this->db->where($wheres);
        }
        $class=get_called_class();
        $result=$this->db->get($class::get_tbl_name());
        if($result)
        {
            $result=$result->result_array();
            if(sizeof($result) && isset($result[0])){
                $res=[];
                foreach($result as $row){
                    $res[$row['id']]=$row;
                }
                return $res;
            }
        }
        return false;
    }
    
    public function check_exist($wheres=array())
    {
        if(is_two_side($wheres))
        {
            foreach($wheres as $where)
            {
                $this->db->where($where,"and","or");
            }
        }
        else {
            $this->db->where($wheres);
        }
        $class=get_called_class();
        $result=$this->db->get($class::get_tbl_name());
        if($result)
        {
            return $result->result_array();
        }
        return false;
    }


    public function get_list($inputs)
    {
        $limit=0;
        $offset=0;
        foreach($inputs as $key=>$value)
        {
            switch ($key) {
                case "limit":
                    $limit=$value['limit'];
                    $offset=$value['offset'];
                    break;
                case "order_by":
                    $this->db->order_by($value['fields'],$value['sort']);
                    break;
                case "where":
                    $this->db->where($value);
                    break;
                case 'where_in':
                    $this->db->where_in($value);
                    break;
                default:
                    break;
            }
        }
        if($limit>0 && $offset>=0)
        {
            $this->db->limit($limit,$offset);
        }
        $class=get_called_class();
        $result=$this->db->get($class::get_tbl_name());
        if($result)
        {
            return $result->result_array();
        }
        return false;
    }
    
    public function insert($infos)
    {
        $class=get_called_class();
        return $this->db->insert($class::get_tbl_name(),$infos);
    }
    /**
     * @author ashrafimanesh
     * @param array $new_infos
     * @param array $wheres
     * @return boolean
     */
    public function update($new_infos=array(),$wheres=array())
    {
        unset($new_infos['id']);
        if(sizeof($wheres))
        {
            $this->db->where($wheres);
        }
        $class=get_called_class();
//        $new_infos['update_date']=time();
//        $new_infos['update_by']=defined("USERID") ? USERID : 0;
        return $this->db->update($class::get_tbl_name(),$new_infos);
    }
    
    
    public function list_infos($model_obj,$inputs=array())
    {
        $c_obj1=  clone $model_obj->db;
        if(isset($inputs['limit']))
        {
            $c_obj1->limit($inputs['limit']['limit'],$inputs['limit']['offset']);
        }
        $c_obj2=  clone $model_obj->db;
        
        $r=$c_obj1->get($model_obj->get_tbl_name());
        
        $c_obj2->select(array("COUNT(*) as cnt"));
        $r2=$c_obj2->get($model_obj->get_tbl_name());
        
        $out=array('data'=>array(),'count'=>0);
        if($r)
        {
            $r=$r->result_array();
            $out['data']=$r;
        }
        if($r2)
        {
            $r2=$r2->result_array();
            $out['count']=$r2[0]['cnt'];
        }
        return $out;
    }
    
    protected function _lastRow($wheres){
        if(sizeof($wheres))
        {
            $this->db->where($wheres);
        }
        $class=get_called_class();
        $this->db->order_by('id','desc');
        $this->db->limit(1);
        $result=$this->db->get($class::get_tbl_name());
        if($result && sizeof($result))
        {
            $result=$result->result_array();
            return $result[0];
        }
        return $result;
    }
    
    public function find($id,$patternClass){
        $class=get_called_class();
        $this->db->where(['id'=>$id]);
        $this->db->order_by('id','desc');
        $result=$this->db->get($class::get_tbl_name());
        if($result)
        {
            $result=$result->result_array();
            if(isset($result[0])){
                $obj=new $patternClass();
                foreach($result[0] as $key=>$value)
                {
                    $obj->{$key}=$value;
                }
                return $obj;
            }
        }
        return false;
    }
}
