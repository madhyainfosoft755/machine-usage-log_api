
<?php



defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        // Load database
        $this->load->database();
    }

    function fetch_all($table,$field,$orderby)
    {
        $this->db->order_by($field,$orderby);
        $query= $this->db->get($table);
        return $query->result_array();
    }

    // Insert user
    public function insert_user($table,$data) {
        return $this->db->insert($table, $data);
    }

    // Update user
    public function update_user($table,$where, $data) {
        $this->db->where($where);
        return $this->db->update($table, $data);
    }

    // Delete user
    public function delete_user($table,$where) {
        $this->db->where($where);
        return $this->db->delete($table);
    }


    //login user
    // public function CheckCredential($table,$email, $password) 
    // {
    //     $this->db->select('*');
    //     $this->db->from($table);
    //     $this->db->where(array('user_email' => $email, 'user_password' => $password)); // Checking both email and password
    //     $this->db->limit(1);
    //     $query = $this->db->get();
    //     if ($query->num_rows() == 1) {
    //         return $query->row_array(); 
    //     } else {
    //         return false;
    //     }
    // }

    public function CheckCredential($email, $password) 
    {
        $this->db->select('users.*, roles.role');
        $this->db->from('users');
        $this->db->join('roles', 'users.role_id = roles.role_id', 'left');
        $this->db->where(array('users.user_email' => $email, 'users.user_password' => $password)); // Checking both email and password
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            return $query->row_array(); 
        } else {
            return false;
        }
    }
    



    public function getUserProfile($table,$user_email)
    {
        $this->db->select('*');
        $this->db->from($table);
        $this->db->where('user_email', $user_email);
        $query = $this->db->get();

        return $query->result_array()[0];
    }



    public function is_exists($table, $where) {
        // Check if a record exists in the specified table based on the given condition
        $this->db->where($where);
        $query = $this->db->get($table);
        return $query->num_rows() > 0;
    }


    public function fetch_with_paginations($table, $order_by_column, $order, $limit, $offset)
    {
        $this->db->order_by($order_by_column, $order);
        $this->db->limit($limit, $offset);
        $query = $this->db->get($table);
        return $query->result_array();
    }


    public function fetch_logs_by_user_id($table, $order_by_column, $order, $limit, $offset, $done_by_id)
    {
        $this->db->where('done_by', $done_by_id);
        $this->db->order_by($order_by_column, $order);
        $this->db->limit($limit, $offset);
        $query = $this->db->get($table);
        return $query->result_array();
    }

   


    public function fetch_with_pagination($table, $order_by_column, $order, $limit, $offset)
    {
        $this->db->select('assigns.*, users.user_name AS user_name, machines.machine_name AS machine_name');
        $this->db->from($table);
        $this->db->join('users', 'assigns.user_id = users.user_id', 'left');
        $this->db->join('machines', 'assigns.machine_id = machines.machine_id', 'left');
        $this->db->order_by($order_by_column, $order);
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        return $query->result_array();
    }
    

    


}













