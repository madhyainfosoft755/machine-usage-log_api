
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE'); //method allowed
        Header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
      
        $this->load->model('Api_model');
        $this->load->library('form_validation');
        $this->load->helper('jwt_helper.php');
        $this->load->helper('verifyAuthToken_helper.php');

     }   


    public function login() {
        $jwt = new JWT();
        $jwtSecretkey = "myloginSecret";
    
        $email = $this->input->post('user_email');
        $password = $this->input->post('user_password');
    
        $user = $this->Api_model->CheckCredential($email,$password);
    
        if ($user) {
            date_default_timezone_set('Asia/Kolkata');
            $date = date('Y-m-d H:i:s', time());
    
            $token = $jwt->encode($user, $jwtSecretkey, 'HS256');
            $result_t = array();
            $result_t['sub'] = $user['user_email'];
            $result_t['exp'] = time() + 172800; //172800;
    
            $data = array(
                'user_name' => $user['user_name'],
                'role' => $user['role_id']
            );
    
            $res = array(
                'status' => 'success',
                'token' => $token,
                'user' => $data,
            );
            echo json_encode($res);
        } else {
            $res = array(
                'status' => 'error',
                'message' => 'Invalid Credentials!',
            );
            echo json_encode($res);
        }
    }
    


    function fetchAllData()
    {
        $this->load->model('api_model');
        $data = $this->api_model->fetch_all('users','user_id', 'ASC');
        if ($data) {
            echo json_encode(array('status'=>'success','data'=>$data));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch data'));
        }
    }
    


    public function insert()
    {
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

       
            


   


