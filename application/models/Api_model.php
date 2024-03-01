
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
}













