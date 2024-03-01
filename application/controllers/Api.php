
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Api_model');
        $this->load->library('form_validation');

    }

    function index()
    {
        $this->load->model('api_model');
        $data = $this->api_model->fetch_all('users','user_id', 'ASC');
        if ($data) {
            echo json_encode(array('status'=>'success','data'=>$data));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch data'));
        }
    }
    


    public function insert() {
        $data = array(
            'user_name'     => $this->input->post('user_name'),
            'user_email'    => $this->input->post('user_email'),
            'user_contact'  => $this->input->post('user_contact'),
            'user_password' => $this->input->post('user_password')
        );

        $result = $this->Api_model->insert_user('users',$data);

        if ($result >0) {
            echo json_encode(array('status' => 'success', 'message' => 'User inserted successfully','user'=>$result));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to insert user'));
        }
    }

    public function update($user_id) {
        $where=array('user_id'=>$user_id);
        $data = array(
            'user_name'     => $this->input->post('user_name'),
            'user_email'    => $this->input->post('user_email'),
            'user_contact'  => $this->input->post('user_contact'),
            'user_password' => $this->input->post('user_password')
        );

        
        $result = $this->Api_model->update_user('users',$where, $data);

        if ($result >0) {
            echo json_encode(array('status' => 'success', 'message' => 'User updated successfully'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to update user'));
        }
    }
    public function delete($user_id) {
        $where=array('user_id'=>$user_id);
        $result = $this->Api_model->delete_user('users',$where);

        if ($result) {
            echo json_encode(array('status' => 'success', 'message' => 'User deleted successfully'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to delete user'));
        }
    }
}

       
            


   


