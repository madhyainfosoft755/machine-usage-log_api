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


    public function verifyAuthToken($token)
    {
        $jwt = new JWT();
        $jwtSecret = 'myloginSecret';
      
        $verification = $jwt->decode($token, $jwtSecret, 'HS256');
        return $verification;
        
       
    }
    public function authUserToken($roleArr)
    {
        $req = $this->input->request_headers();
        if (array_key_exists('Authorization', $req)) {
            $token = ltrim(substr($req['Authorization'], 6));
            
            $token_data = $this->verifyAuthToken($token);
            //  print_r($token_data);
            date_default_timezone_set('Asia/Kolkata');
            $current_date = date('Y-m-d H:i:s', time());
            $token_date = date("Y-m-d H:i:s", $token_data->exp);

            // echo strtotime($current_date);
            // echo strtotime($token_date);
            // echo strtotime($current_date) - strtotime($token_date);

            if ((strtotime($current_date) - strtotime($token_date)) < 0) {
                // get role from email
                $user_email = $token_data->sub;
                // return data getting by email
                $res = $this->Api_model->getUserProfile('users', $user_email);
                // print_r($res);
                $role = $res['role_id'];
                // if role of user is exist in $role arrya ten return false else data
                if (in_array($role, $roleArr)) {
                    return $res;
                } else {
                    //role is not matched means not autheticated for this action
                    // echo "false";
                    return false;
                }
                
            } else {
                // if tooken invalid or expired then return
                return false;
            }
        } else {
            //if auth key not in header then return
            return false;
        }
    } 

    public function Userlogin() {
        $jwt = new JWT();
        $jwtSecretkey = "myloginSecret";
    
        $email = $this->input->post('user_email');
        $password = $this->input->post('user_password');
    
        $user = $this->Api_model->CheckCredential($email,$password);
    
        if ($user) {
            date_default_timezone_set('Asia/Kolkata');
            $date = date('Y-m-d H:i:s', time());
    
            $result_t = array();
            $result_t['sub'] = $user['user_email'];
            $result_t['exp'] = time() + 172800; //172800;
            $token = $jwt->encode($result_t, $jwtSecretkey, 'HS256');

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

    public function getUserProfile()
    {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Token is valid
            // Your getProfile logic 
            $res = array(
                'status' => 'success',
                'user' => $token_data,
            );
            echo json_encode($res);
        } elseif ($this->authUserToken() === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'User Unauthorized'));
        }
    }

    public function fetchAllUserData()
    {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // all fetchAllData logic 
            $data = $this->Api_model->fetch_all('users','user_id', 'ASC');
            if ($data) {
                echo json_encode(array('status'=>'success','data'=>$data));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch data'));
            }
        } elseif ($this->authUserToken() === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'User Unauthorized'));
        }
    }

   
    public function UserInsert()
    {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Token is valid
           // insart logic
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
        } elseif ($this->authUserToken() === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'User not logged in'));
        }
    }



    public function UserUpdate($user_id) {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // valide token
            //  update logic  
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

        } elseif ($this->authUserToken() === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'User Unauthorized'));
        }
    }

    public function UserDelete($user_id) {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            //  delete logic here  
            $where=array('user_id'=>$user_id);
            $result = $this->Api_model->delete_user('users',$where);
    
            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'User deleted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to delete user'));
            }
        } elseif ($this->authUserToken() === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'User Unauthorized'));
        }
    }
    
    
   
    
}

  
            


   


