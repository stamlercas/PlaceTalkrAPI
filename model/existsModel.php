<?php

class ExistsModel
{
    protected $db;
    
    function __construct()
    {
        $this->db = new DB();
    }
    
    public function placeExists($placeID)
    {
        return $this->exists('places', $placeID, 'PlaceID');
    }
    
    public function usernameExists($username)
    {
        return $this->exists('users', $username, 'Username');
    }
    
    public function emailExists($email)
    {
        return $this->exists('users', $email, 'Email');
    }
    
    private function exists($table, $value, $field)
    {
        $this->db = new DB();
        $this->db->bind('value', $value);
        $result = $this->db->query("SELECT COUNT(*) AS `Count` FROM $table WHERE $field = :value");
        if ($result[0]['Count'] == 0)
            return false;
        return true;
    }
    
}
