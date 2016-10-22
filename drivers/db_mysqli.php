<?php

/**
 * Description of mysql
 *
 * @author ramin ashrafimanesh <ashrafimanesh@gmail.com>
 */
class db_mysqli implements database{
    
    public $wheres,$select,$from,$limit,$offset,$db,$order_by,$join,$prefix_sql,$postix_sql,$group_by;
    public $last_query,$error;
    protected $_trans_status=true;


    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;
    
    /**
     * return singleton instance from same host,database,user,pass
     * @param string $dbname
     * @param string $host
     * @param string $user
     * @param string $pass
     * @return object
     */
    public static function get_instance($dbname="paylasky",$host="localhost",$user="root",$pass="123")
    {
        $code=md5($host.'-'.$user.'-'.$pass.'-'.$dbname);
        if (null === static::$instance[$code]) {
            
            static::$instance[$code]=new db_mysqli(mysqli_connect($host, $user, $pass, $dbname));
        }
        
        return static::$instance[$code];
    }    
    
    protected function __construct($connection_bject) {
        $this->db=$connection_bject;
        
        mysqli_set_charset($this->db,"utf8");
        $this->reset_vars();
    }
    
    public function reset_vars()
    {
        $this->wheres=array();
        $this->last_query='';
        $this->from='';
        $this->select=array();
        $this->join=array();
        $this->limit=0;
        $this->order_by=array();
        $this->group_by=array();
        $this->offset=-1;
        $this->error='';
        $this->_trans_status=true;
    }
    
    public function connect()
    {
        
    }

    
    /**
     * run mysqli query with mysqli connection object
     * @param string $query
     */
    public function query($query)
    {
        $result=$this->db->query($query);
        $this->reset_vars();
        $this->last_query=$query;
        if($result)
        {
            $ret=(new db_mysqli_result($result,$this->db));
//            @mysqli_next_result($this->db);
                        mysqli_more_results($this->db);
            return $ret;
        }
        $this->_trans_status=false;
        $this->error=  $this->db->error;
        return false;
    }
    
    /**
     * insert to table
     * @param string $to table name
     * @param array $datas field=>value | if have multi insert 0=>(field=>value),1=>(field=>value)
     * @return mixed(integer: inserted id at one row,true: on multiple insert , false on error
     */
    public function insert($to,$datas=array())
    {
        $this->last_query='';

        $sql="INSERT INTO `$to` ";
        $field_str='(';
        $value_str=' VALUES ';
        $is_two_side=is_two_side($datas);
        if(!$is_two_side)
        {
            $value_str.=" ( ";
            foreach($datas as $field=>$value)
            {
                $field_str.="$to.$field".',';
                $value_str.="'".$this->real_escape($value)."',";

            }
            $value_str=trim($value_str,',').")";
        }
        else
        {
            $set_field=true;
            foreach($datas as $rows)
            {
                $value_str.=" ( ";
                foreach($rows as $field=>$value)
                {
                    if($set_field)
                    {
                        $field_str.="$to.$field".',';
                    }
                    $value_str.="'".$this->real_escape($value)."',";
                }
                $set_field=false;
                $value_str=trim($value_str,',')."),";
            }
            $value_str=trim($value_str,',');
        }
        $field_str=trim($field_str,',').")";
        
        $sql.=$field_str.$value_str;
        $result=$this->query($sql);
        if($result)
        {
            if(!$is_two_side)
            {
                return $result->inserted_id();
            }
            return true;
        }
        return false;
    }
    
    public function update($table,$new_values)
    {
        $sql=" UPDATE $table SET ";
        foreach($new_values as $field=>$value)
        {
            if(is_array($value))
                $sql.=" `$field` = `$field` $value[0] '".$this->real_escape($value[1])."',";
            else
                $sql.=" `$field` = '".$this->real_escape($value)."',";
        }
        $sql=trim($sql,",");
        if(sizeof($this->wheres))
        {
            $sql.=" WHERE ";
            foreach($this->wheres as $conditions)
            {
                $sql.=$conditions;
            }
        }
        return $this->query($sql);
    }
    
    public function real_escape($str)
    {
        return mysqli_real_escape_string($this->db,$str);
    }
    

    /**
     * 
     * @param array $wheres field=>condition value
     * @param type $operand
     */
    public function where($wheres=array(),$operand="and",$befor_operand="and")
    {
        $operand=  strtoupper($operand);
        $befor_operand=  strtoupper($befor_operand);
        $cnt=sizeof($this->wheres);
        $this->wheres[$cnt]=($cnt>0) ? " ".$befor_operand." " : "";
        $this->wheres[$cnt].=' (';
        foreach($wheres as $field=>$condition)
        {
            if(is_array($condition))
            {
                $this->wheres[$cnt].=" $field $condition[0] '".$this->real_escape($condition[1])."' $operand";
            }
            else
            {
                $this->wheres[$cnt].=" $field = '".$this->real_escape($condition)."' $operand";
            }
        }
        $this->wheres[$cnt]=trim($this->wheres[$cnt],$operand);
        $this->wheres[$cnt].=')';
    }
    
    public function where_in($wheres=array(),$operand="and",$befor_operand="and")
    {
        $this->_where_in("IN", $wheres, $operand, $befor_operand);
    }
    
    public function where_not_in($wheres=array(),$operand="and",$befor_operand="and"){
        $this->_where_in("NOT IN", $wheres, $operand, $befor_operand);
    }


    protected function _where_in($in="IN",$wheres=array(),$operand="and",$befor_operand="and"){
        $operand=  strtoupper($operand);
        $befor_operand=  strtoupper($befor_operand);
        $cnt=sizeof($this->wheres);
        $this->wheres[$cnt]=($cnt>0) ? " ".$befor_operand." " : "";
        $this->wheres[$cnt].=' (';
        foreach($wheres as $field=>$conditions)
        {
            $wh='';
            if(is_array($conditions))
            {
                foreach($conditions as $condition)
                {
                    $wh.="'{$this->real_escape($condition)}',";
                }
            }
            else
            {
                $wh.="{$this->real_escape($conditions)},";
            }
            $wh=trim($wh,",");
            $this->wheres[$cnt].=" $field $in (".$wh.") $operand";
        }
        $this->wheres[$cnt]=trim($this->wheres[$cnt],$operand);
        $this->wheres[$cnt].=')';
    }


    public function select($fields=array())
    {
        if(!is_array($fields))
        {
            $fields=array($fields);
        }
        foreach ($fields as $field) {
            $this->select[$field]=$field;
        }
    }
    
    /**
     * set limit,offset
     * @param int $limit
     * @param int $offset
     */
    public function limit($limit=0,$offset=-1)
    {
        if($limit>0)
        {
            $this->limit=$limit;
            if($offset>=0)
            {
                $this->offset=$offset;
            }
        }
    }
    
    public function get($from='')
    {
        $sql="SELECT ";
        if(sizeof($this->select))
        {
            $sql.=implode(",", $this->select)." ";
        }
        else
        {
            $sql.="* ";
        }
        if($from)
        {
            $sql.=" FROM $from ";
        }
        if(sizeof($this->join))
        {
            foreach($this->join as $j)
            {
                foreach($j as $tbl_name=>$on)
                {
                    $sql.=" JOIN $tbl_name ON $on ";
                }
            }
        }
        if(sizeof($this->wheres))
        {
            $sql.=" WHERE ";
            foreach($this->wheres as $conditions)
            {
                $sql.=$conditions;
            }
        }
        if(sizeof($this->group_by))
        {
            $sql.=" GROUP BY ";
            foreach($this->group_by as $f)
            {
                $sql.="$f,";
            }
            $sql=trim($sql,',');
        }
        if(sizeof($this->order_by))
        {
            $sql.=" ORDER BY ";
            foreach($this->order_by as $f=>$sort)
            {
                $sql.="$f,";
            }
            $sql=trim($sql,',')." $sort ";
        }
        if($this->limit>0)
        {
            if($this->offset>=0)
            {
                $sql.=" LIMIT ".$this->offset;
                    $sql.=",".$this->limit;
            }
            else
            {
                $sql.=" LIMIT ".$this->limit;
            }
        }
        return $this->query($sql);
    }
    
    public function order_by($field,$sort='ASC')
    {
        $this->order_by[$field]=$sort;
    }
    
    public function group_by($field)
    {
        if(!is_array($field))
        {
            $field=array($field);
        }
        $this->group_by=  array_merge($this->group_by, $field);
    }
    
    public function join($tbl_name,$on)
    {
        $this->join[]=array($tbl_name=>$on);
    }
    
    public function trans_fail()
    {
        $this->trans_rollback();
    }
    
    public static function last_db()
    {
        return end(self::$instance);
    }


    public function trans_complete()
    {
        if($this->_trans_status)
        {
            $this->trans_commit();
            return true;
        }
        else
        {
            $this->trans_rollback();
            return false;
        }
    }
    
    public function trans_start()
    {
        $this->trans_begin();
    }
    
    protected function trans_begin()
    {
        $this->query("SET AUTOCOMMIT=0");
        $this->query("START TRANSACTION");
    }
    
    protected function trans_commit()
    {
        $this->query('COMMIT');
        $this->query('SET AUTOCOMMIT=1');
    }
    
    protected function trans_rollback()
    {
        $this->query('ROLLBACK');
        $this->query('SET AUTOCOMMIT=1');
    }
        
}

class db_mysqli_result
{
    private $result,$mysqli;
    public function __construct($result,$mysqli) {
        $this->result=$result;
        $this->mysqli=$mysqli;
    }
    
    public function result_array()
    {
        $res=array();
        while($row = $this->result->fetch_array(MYSQLI_ASSOC))
        {
            $res[]=$row;
        }
        return $res;
    }
    
    public function inserted_id()
    {
        return $this->mysqli->insert_id;
    }
    
}

