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
                'role' => $user['role']
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
        } elseif ($token_data === false) { // Corrected function call
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
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
        } elseif ($token_data === false) {            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }

   



    public function UserInsert()
    {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Token is valid

            // Fetch input data
            $user_name     = $this->input->post('user_name');
            $user_email    = $this->input->post('user_email');
            $user_contact  = $this->input->post('user_contact');
            $user_password = $this->input->post('user_password');

            // Check if user with the given email already exists
            $is_user_exists = $this->Api_model->is_exists('users', array('user_email' => $user_email));

            if ($is_user_exists) {
                // User with the given email already exists
                echo json_encode(array('status' => 'error', 'message' => 'This Email already exists.'));
                return;
            } else {
                // Insert new user
                $data = array(
                    'user_name'     => $user_name,
                    'user_email'    => $user_email,
                    'user_contact'  => $user_contact,
                    'user_password' => $user_password
                );

                $result = $this->Api_model->insert_user('users', $data);

                if ($result > 0) {
                    echo json_encode(array('status' => 'success', 'message' => 'User inserted successfully', 'user' => $result));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'Failed to insert user'));
                }
            }
        } elseif ($token_data === false) {            // Token is invalid
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

        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }

    public function UserDelete($user_id) {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            //  delete logic here  
            $is_machine_exists = $this->Api_model->is_exists('users', array('user_id' => $user_id));
            // Check if department name already exists
            if ($is_machine_exists) {
                // Machine with the given ID does not exist
                echo json_encode(array('status' => 'error', 'message' => 'This Email  already exists.'));
                return;
              } else {
                $where=array('user_id'=>$user_id);
                $result = $this->Api_model->delete_user('users',$where);
        
                if ($result) {
                    echo json_encode(array('status' => 'success', 'message' => 'User deleted successfully'));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'Failed to delete user'));
                }
            }    
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }
    
    
    public function fetchAllDepartmentData()
    {
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // all fetchAllData logic 
            $data = $this->Api_model->fetch_all('department','department_id', 'ASC');
            if ($data) {
                echo json_encode(array('status'=>'success','data'=>$data));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch data'));
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }



    public function DepartmentInsert()
    {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Token is valid
            
            // Decode JSON input
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            // Get department name from the input
            $department_name = $_POST['department_name'];
            $is_machine_exists = $this->Api_model->is_exists('department', array('department_name' => $department_name));
            // Check if department name already exists
            if ($is_machine_exists) {
                // Machine with the given ID does not exist
                echo json_encode(array('status' => 'error', 'message' => 'Department with the same name already exists.'));
                return;
              } else {
                // Department with the same name does not exist, proceed with insertion
                $data = array(
                    'department_name' => $department_name
                );
                $result = $this->Api_model->insert_user('department', $data);
    
                if ($result) {
                    echo json_encode(array('status' => 'success', 'message' => 'Department inserted successfully'));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'Failed to insert department'));
                }
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token OR Only admin  can insert departments'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }
   

    public function DepartmentUpdate($department_id) {
        $token_data = $this->authUserToken([1]); // Assuming 1 represents admin role
        if ($token_data) {
            // Valid token
            // Update logic
            $_POST = json_decode(file_get_contents('php://input'), true);
            // Get department name from the input
            $department_name = $_POST['department_name'];          
            // Construct the where clause for the update
            $where = array('department_id' => $department_id);
            // Data to be updated
            $data = array(
                'department_name' => $department_name
            );
            // Perform the update operation
            $result = $this->Api_model->update_user('department', $where, $data);

            if ($result > 0) {
                echo json_encode(array('status' => 'success', 'message' => 'Department updated successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to update department'));
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User is not authorized
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }


    public function DepartmentDelete($department_id) {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            //  delete logic here 
            $is_machine_exists = $this->Api_model->is_exists('department', array('department_id' => $department_id));

            if (!$is_machine_exists) {
                // Machine with the given ID does not exist
                echo json_encode(array('status' => 'error', 'message' => 'department with the given ID does not exist'));
                return;
            } 
            $where=array('department_id'=>$department_id);
            $result = $this->Api_model->delete_user('department',$where);
    
            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'Department deleted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to delete Department'));
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }


    public function fetchAllMachinesData()
    {
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // all fetchAllData logic 
            $data = $this->Api_model->fetch_all('machines','machine_id', 'ASC');
            if ($data) {
                echo json_encode(array('status'=>'success','data'=>$data));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch data'));
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }


    public function MachinesInsert()
    {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Token is valid
            
            // Decode JSON input
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            // Get department name from the input
            $machine_name = $_POST['machine_name'];
            $is_machine_exists = $this->Api_model->is_exists('machines', array('machine_name' => $machine_name));

            if ($is_machine_exists) {
                // Machine with the given ID does not exist
                echo json_encode(array('status' => 'error', 'message' => ' Machines with the same name already exists.'));
                return;
            } else {
                // Department with the same name does not exist, proceed with insertion
                $data = array(
                    'machine_name' => $machine_name
                );
                $result = $this->Api_model->insert_user('machines', $data);
    
                if ($result) {
                    echo json_encode(array('status' => 'success', 'message' => ' Machines inserted successfully'));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'Failed to insert  data'));
                }
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token OR Only admin  can insert data'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }
   

    public function MachinesUpdate($machine_id) {
        $token_data = $this->authUserToken([1]); // Assuming 1 represents admin role
        if ($token_data) {
            // Valid token
            // Update logic
            $_POST = json_decode(file_get_contents('php://input'), true);
            // Get department name from the input
            $machine_name = $_POST['machine_name'];          
            // Construct the where clause for the update
            $where = array('machine_id' => $machine_id);
            // Data to be updated
            $data = array(
                'machine_name' => $machine_name
            );
            // Perform the update operation
            $result = $this->Api_model->update_user('machines', $where, $data);

            if ($result > 0) {
                echo json_encode(array('status' => 'success', 'message' => ' Machines updated successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to update  data'));
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User is not authorized
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }



    public function MachinesDelete($machine_id) {
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Valid token
            
            // Check if the machine with the given ID exists
            $is_machine_exists = $this->Api_model->is_exists('machines', array('machine_id' => $machine_id));

            if (!$is_machine_exists) {
                // Machine with the given ID does not exist
                echo json_encode(array('status' => 'error', 'message' => 'Machine with the given ID does not exist'));
                return;
            }

            // Delete the machine
            $where = array('machine_id' => $machine_id);
            $result = $this->Api_model->delete_user('machines', $where);

            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'Machine deleted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to delete data'));
            }
        } elseif ($token_data === false) {            // Token is invalid
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not authorized
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }


    //url  http://localhost/machine-usage-log_api/index.php/api/fetchAllAssignData
    public function fetchAllAssignData($page = 1, $records = 10)
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1, 2]);
        if ($token_data) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // Fetch data with pagination
                $data = $this->Api_model->fetch_with_pagination('assigns', 'assign_id', 'ASC', $records, $offset);
    
                if ($data) {
                    echo json_encode(array('status' => 'success', 'data' => $data));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch data'));
                }
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Invalid page number or records per page'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }      

      
    public function AssignInsert() {
        $user = $this->authUserToken([1]);
        if ($user) {
            // Get data from the POST request
            $_POST = json_decode(file_get_contents('php://input'), true);
            // Get user_id and machine_id from the input
            $user_id = $_POST['user_id'];
            $machine_id = $_POST['machine_id'];
    
            // Check if user_id exists
            $is_user_exists = $this->Api_model->is_exists('users', array('user_id' => $user_id));
    
            // Check if machine_id exists
            $is_machine_exists = $this->Api_model->is_exists('machines', array('machine_id' => $machine_id));
    
            // If both user_id and machine_id exist, proceed with insertion
            if ($is_user_exists && $is_machine_exists) {
                $data = array(
                    'user_id' => $user_id,
                    'machine_id' => $machine_id,
                    'assigned_by' => $user['user_id']
                );
    
                // Call the model method to insert the assignment
                $result = $this->Api_model->insert_user('assigns',$data);
    
                // Check if insertion was successful
                if ($result) {
                    // Return success response
                    echo json_encode(array('status' => 'success', 'message' => 'Assign added successfully'));
                } else {
                    // Return error response
                    echo json_encode(array('status' => 'error', 'message' => 'Failed to add assign'));
                }
            } elseif (!$is_user_exists && !$is_machine_exists) {
                // If both user_id and machine_id do not exist
                echo json_encode(array('status' => 'error', 'message' => 'Invalid user_id and machine_id'));
            } elseif (!$is_user_exists) {
                // If user_id does not exist
                echo json_encode(array('status' => 'error', 'message' => 'Invalid user_id'));
            } else {
                // If machine_id does not exist
                echo json_encode(array('status' => 'error', 'message' => 'Invalid machine_id'));
            }
        } elseif ($user === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not authorized
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    }


    public function Machine_logsInsert()
    {
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // Token is valid
            
            // Decode JSON input
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            // Get data from the input
            $data = array(
                'date' => $_POST['date'],
                'location' => $_POST['location'],
                'format' => $_POST['format'],
                'shift' => $_POST['shift'],
                'machine' => $_POST['machine'],
                'product' => $_POST['product'],
                'batch' => $_POST['batch'],
                'op_st_time' => $_POST['op_st_time'],
                'op_ed_time' => $_POST['op_ed_time'],
               'cl_st_time' => $_POST['cl_st_time'],
               'cl_ed_time' => $_POST['cl_ed_time'],
                'done_by' => $_POST['done_by'],
                'check_by' => $_POST['check_by'],
                'remarks' => $_POST['remarks']
            );
    
            // Insert data into machine_logs table
            $result = $this->Api_model->insert_user('machine_logs', $data);
    
            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'Data inserted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to insert data'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token OR Only admin can insert data'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }
    

    public function fetchAllMachine_logsData($page = 1, $records = 10)
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // Fetch data with pagination
                $data = $this->Api_model->fetch_with_paginations('machine_logs', 'log_id', 'ASC', $records, $offset);
    
                if ($data) {
                    echo json_encode(array('status' => 'success', 'data' => $data));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'Failed to fetch data'));
                }
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Invalid page number or records per page'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => ' Unauthorized'));
        }
    } 




    
}

  
            


   


