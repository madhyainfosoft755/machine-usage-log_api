
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



    public function is_department_exists($department_name) {
        // Query to check if department name already exists
        $this->db->where('department_name', $department_name);
        $query = $this->db->get('department');
        return $query->num_rows() > 0;
    }

}













