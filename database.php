<?php
/**
 * Description of interface_database
 *
 * @author ashrafimanesh
 */
interface database {
    
    public static function get_instance($dbname="active-record",$host="localhost",$user="root",$pass="");
    
    public function connect();
    
    
    public function query($query);
    /**
     * result of this where firstname=>"ramin" operand lastname=>"ashrafimanesh"
     * @param type $wheres firstname=>"ramin", lastname=>"ashrafimanesh"
     * @param type $operand
     */
    public function where($wheres=array(),$operand="and");
    public function select($fields=array());
    public function real_escape($str);
    public function get($from);
    /**
     * 
     * @param type $to
     * @param array $datas firstname=>"ramin",lastname=>"ashrafimanesh"
     */
    public function insert($to,$datas=array());

    //put your code here
}
