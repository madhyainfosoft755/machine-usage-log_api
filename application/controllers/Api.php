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
                if ($res['is_active']==1){
                    // active this if roles defined for user in future
                    // if (in_array($role, $roleArr)) {
                    //     return $res;
                    // } else {
                    //     //role is not matched means not autheticated for this action
                    //     // echo "false";
                    //     return false;
                    // }

                    return $res;
                } else{
                    // user is inactive
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
        $where = array( 'user_email'=> $email );
        $users = $this->Api_model->get_where('users', $where);
    
        if ($users) {
            $user = $users[0];
            if($user['user_password'] == $password){
                if($user['is_active'] == 1){

                    date_default_timezone_set('Asia/Kolkata');
                    $date = date('Y-m-d H:i:s', time());
            
                    $result_t = array();
                    $result_t['sub'] = $user['user_email'];
                    $result_t['exp'] = time() + 172800; //172800;
                    $token = $jwt->encode($result_t, $jwtSecretkey, 'HS256');
                    if($user['is_superadmin']==1){ $role = 'superadmin'; }
                    else if($user['is_admin']==1){ $role = 'admin'; }
                    else{ $role = 'user'; }
                    // If in future categorization added for user then we will send role as user plus one more key value pair,
                    // "type" and value the categorization of user, like manager of others
        
                    $data = array(
                        'user_name' => $user['user_name'],
                        'role' => $role
                    );
            
                    $res = array(
                        'status' => 'success',
                        'token' => $token,
                        'user' => $data,
                    );

                } else { $res = array( 'status' => 'error', 'message' => 'Inactive User!', ); }
            } else { $res = array( 'status' => 'error', 'message' => 'Invalid Credentials!', ); }
        } else { $res = array( 'status' => 'error', 'message' => 'Invalid Credentials!', ); }
        echo json_encode($res);
    }
    
    
    public function active($user_id) {
        // Check user's role to ensure authorization
        $tokendata = $this->authUserToken(['admin', 'superadmin']);
        if (!$tokendata) {
            // Activate the user with $user_id
            $where= array('is_active' => 1);
            $this->Api_model->is_active(array('user_id'=>$user_id),'users', $where);
    
            $res = array(
                'status' => 'success',
                'message' => ' Active successfully!',
            );
        } else {
            $res = array(
                'status' => 'error',
                'message' => 'Unauthorized access!',
            );
        }
        echo json_encode($res);
    }

    
    public function deactive($user_id) {
        // Check user's role to ensure authorization
        $tokendata = $this->authUserToken(['admin', 'superadmin']);
        if (!$tokendata) {
            // Deactivate the user with $user_id
            $where= array('is_active' => 0);
            $this->Api_model->is_active(array('user_id'=>$user_id),'users', $where);
    
            $res = array(
                'status' => 'success',
                'message' => ' Deactive successfully!',
            );
        } else {
            $res = array(
                'status' => 'error',
                'message' => 'Unauthorized access!',
            );
        }
        echo json_encode($res);
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
            $created_by = $token_data['user_id'];
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
                    'machine_name' => $machine_name,
                    'created_by' => $created_by
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



    public function Usage_logsInsert()
    {
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // Token is valid
            
            // Decode JSON input
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            // Get the user's ID from the token data
            $user_id = $token_data['user_id'];
            
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
                'done_by' => $user_id, // Set done_by to the user's ID
                'check_by' => $_POST['check_by'],
                'remarks' => $_POST['remarks']
            );

            // Insert data into usage_logs table
            $result = $this->Api_model->insert_user('usage_logs', $data);

            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'Data inserted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to insert data'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    

    public function fetchAllUsage_logsData($page = 1, $records = 10)
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // Fetch data with pagination
                $data = $this->Api_model->fetch_with_paginations('usage_logs', 'log_id', 'ASC', $records, $offset);
    
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

    

    public function fetchAllUsage_logsUser_Data($page = 1, $records = 10)
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Get the user ID of the user who inserted the data
               
                $user_id = $user['user_id'];
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // If user is admin, fetch all logs
                if ($user['role_id'] ==1) {
                    $data = $this->Api_model->fetch_with_paginations('usage_logs', 'log_id', 'ASC', $records, $offset);

                } else {
                   
                    // Fetch data with pagination based on user's ID
                    $data = $this->Api_model->fetch_logs_by_user_id('usage_logs', 'log_id', 'ASC', $records, $offset, $user_id);
                }
    
                if ($data) {
                    echo json_encode(array('status' => 'success', 'data' => $data));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'No data available'));
                }
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Invalid page number or records per page'));
            }
        } elseif ($user === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }
    

    public function UserInsertExcelFileUpload()
    {
        // Check if the user is authenticated
        $token_data = $this->authUserToken([1]);
        if (!$token_data) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token OR Only admin can insert data'));
            return;
        }

        // Check if a file is uploaded
        if (empty($_FILES['userfile']['name'])) {
            echo json_encode(array('status' => 'error', 'message' => 'No file uploaded'));
            return;
        }

        // File upload configuration
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'xlsx|xls';
        $config['max_size'] = '2048'; // 2MB max file size

        $this->load->library('upload', $config);

        // Attempt file upload
        if (!$this->upload->do_upload('userfile')) {
            $error = $this->upload->display_errors();
            echo json_encode(array('status' => 'error', 'message' => 'Error uploading file: ' . $error));
            return;
        }

        // Retrieve file data and path
        $fileData = $this->upload->data();
        $filePath = $fileData['full_path']; // Full path to the uploaded file

        // Load required library for Excel processing
        $this->load->library('excel');
        $objPHPExcel = PHPExcel_IOFactory::load($filePath);
        $worksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $successCount = 0;
        $errorCount = 0;

        // Iterate over each row in the Excel file
        for ($row = 2; $row <= $highestRow; $row++) {
            // Retrieve data from Excel file
            $user_name = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
            $user_email = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
            $user_contact = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $user_password = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            $role_id = $worksheet->getCellByColumnAndRow(4, $row)->getValue() ?? 2;

            // Check if user with the given email already exists
            $is_email_exists = $this->Api_model->is_exists('users', array('user_email' => $user_email));
            if ($is_email_exists) {
                echo json_encode(array('status' => 'error', 'message' => 'User with this email already exists'));
                $errorCount++;
                continue;
            }
            // Check if contact number is null and append to error message
            if (empty($user_contact)) {
                $errorMessage = "Contact number cannot be empty for user: $user_name, email: $user_email";
                echo json_encode(array('status' => 'error', 'message' => $errorMessage));
                $errorCount++;
                continue;
            }

            // Check if required fields are provided
            if (empty($user_name) || empty($user_email) || empty($user_password)) {
                echo json_encode(array('status' => 'error', 'message' => 'User name, email, and password are required'));
                $errorCount++;
                continue;
            }

            // Check if user_contact is empty, set to null if empty
            if (empty($user_contact)) {
                $user_contact = null;
            }

            // Insert data into the database
            $data = array(
                'user_name' => $user_name,
                'user_email' => $user_email,
                'user_contact' => $user_contact,
                'user_password' => $user_password,
                'role_id' => $role_id,
                'created_at' => date('Y-m-d H:i:s')
            );
            $result = $this->Api_model->insert_user('users', $data);

            if ($result > 0) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        // Prepare and echo the response
        if ($successCount > 0) {
            echo json_encode(array('status' => 'success', 'message' => 'Data inserted successfully', 'success_count' => $successCount));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to insert data'));
        }
    }



    public function MachineInsertExcelFileUpload()
    {
        // Check if the user is authenticated
        $token_data = $this->authUserToken([1]);
        if (!$token_data) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token OR Only admin can insert data'));
            return;
        }

        // Check if a file is uploaded
        if (empty($_FILES['machinefile']['name'])) {
            echo json_encode(array('status' => 'error', 'message' => 'No file uploaded'));
            return;
        }

        // File upload configuration
        $config['upload_path'] = './machine_upload/';
        $config['allowed_types'] = 'xlsx|xls';
        $config['max_size'] = '2048'; // 2MB max file size

        $this->load->library('upload', $config);

        // Attempt file upload
        if (!$this->upload->do_upload('machinefile')) {
            $error = $this->upload->display_errors();
            echo json_encode(array('status' => 'error', 'message' => 'Error uploading file: ' . $error));
            return;
        }
        // Retrieve file data and path
        $fileData = $this->upload->data();
        $filePath = $fileData['full_path']; // Full path to the uploaded file

        // Load required library for Excel processing
        $this->load->library('excel');
        $objPHPExcel = PHPExcel_IOFactory::load($filePath);
        $worksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $successCount = 0;
        $errorCount = 0;

        // Iterate over each row in the Excel file
        for ($row = 2; $row <= $highestRow; $row++) {
            // Retrieve data from Excel file
            $machine_name = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
            // $created_at = date('Y-m-d H:i:s');
            $created_by = $token_data['user_id']; // Using user_id from the authentication token         

            // Check if user with the given email already exists
            $is_machine_exists = $this->Api_model->is_exists('machines', array('machine_name' => $machine_name));
            if ($is_machine_exists) {
                echo json_encode(array('status' => 'error', 'message' => 'This machine name already exists'));
                $errorCount++;
                continue;
            }

            // Check if required fields are provided
            if (empty($machine_name)) {
                echo json_encode(array('status' => 'error', 'message' => 'Machine name is required'));
                $errorCount++;
                continue;
            }

            // Insert data into the database
            $data = array(
                'machine_name' => $machine_name,
                'created_by' => $created_by
            );
            $result = $this->Api_model->insert_user('machines', $data);

            if ($result > 0) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        // Prepare and echo the response
        if ($successCount > 0) {
            echo json_encode(array('status' => 'success', 'message' => 'Data inserted successfully', 'success_count' => $successCount));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to insert data'));
        }
    }


    public function Cleaning_logsInsert()
    {
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // Token is valid
            
            // Decode JSON input
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            // Get the user's ID from the token data
            $user_id = $token_data['user_id'];
            
            // Get data from the input
            $data = array(
                'date' => $_POST['date'],
                'location' => $_POST['location'],
                'format' => $_POST['format'],
                'shift' => $_POST['shift'],
                'machine' => $_POST['machine'],
                'product' => $_POST['product'],
                'batch' => $_POST['batch'],
                'cl_st_time' => $_POST['cl_st_time'],
                'cl_ed_time' => $_POST['cl_ed_time'],
                'done_by' => $user_id, // Set done_by to the user's ID
                'check_by' => $_POST['check_by'],
                'remarks' => $_POST['remarks']
            );

            // Insert data 
            $result = $this->Api_model->insert_user('cleaning_logs', $data);

            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'Data inserted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to insert data'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function fetchAllCleaning_logsData($page = 1, $records = 10)
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // Fetch data with pagination
                $data = $this->Api_model->fetch_with_paginations('cleaning_logs', 'cleaning_id', 'ASC', $records, $offset);
    
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

    public function fetchAllCleaning_logsUser_Data($page = 1, $records = 10)
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Get the user ID of the user who inserted the data
               
                $user_id = $user['user_id'];
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // If user is admin, fetch all logs
                if ($user['role_id'] ==1) {
                    $data = $this->Api_model->fetch_with_paginations('cleaning_logs', 'cleaning_id', 'ASC', $records, $offset);

                } else {
                   
                    // Fetch data with pagination based on user's ID
                    $data = $this->Api_model->fetch_logs_by_user_id('cleaning_logs', 'cleaning_id', 'ASC', $records, $offset, $user_id);
                }
    
                if ($data) {
                    echo json_encode(array('status' => 'success', 'data' => $data));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'No data available'));
                }
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Invalid page number or records per page'));
            }
        } elseif ($user === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }


    public function Maintenance_logsInsert()
    {
        $token_data = $this->authUserToken([1,2]);
        // Token is valid  
        if ($token_data) {                    
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
                'm_st_time' => $_POST['m_st_time'],
                'm_ed_time' => $_POST['m_ed_time'],
                'done_by' =>  $_POST['done_by'], 
                'check_by' => $_POST['check_by'],
                'remarks' => $_POST['remarks']
            );

            // Insert data 
            $result = $this->Api_model->insert_user('maintenance_logs', $data);

            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'Data inserted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to insert data'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function fetchAllMaintenance_logsData($page = 1, $records = 10)
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // Fetch data with pagination
                $data = $this->Api_model->fetch_with_paginations('maintenance_logs', 'maintenance_id', 'ASC', $records, $offset);
    
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

    public function fetchAllMaintenance_logsUser_Data($page = 1, $records = 10)
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Get the user ID of the user who inserted the data
               
                $user_id = $user['user_id'];
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // If user is admin, fetch all logs
                if ($user['role_id'] ==1) {
                    $data = $this->Api_model->fetch_with_paginations('maintenance_logs', 'maintenance_id', 'ASC', $records, $offset);

                } else {
                   
                    // Fetch data with pagination based on user's ID
                    $data = $this->Api_model->fetch_logs_check_by_user_id('maintenance_logs', 'maintenance_id', 'ASC', $records, $offset, $user_id);
                }
    
                if ($data) {
                    echo json_encode(array('status' => 'success', 'data' => $data));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'No data available'));
                }
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Invalid page number or records per page'));
            }
        } elseif ($user === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function Breakdown_logsInsert()
    {
        $token_data = $this->authUserToken([1,2]);
        // Token is valid  
        if ($token_data) {                    
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
                'break_st_time' => $_POST['break_st_time'],
                'break_ed_time' => $_POST['break_ed_time'],
                'done_by' =>  $_POST['done_by'], // Set done_by to the user's ID
                'check_by' => $_POST['check_by'],
                'remarks' => $_POST['remarks']
            );

            // Insert 
            $result = $this->Api_model->insert_user('breakdown_logs', $data);

            if ($result) {
                echo json_encode(array('status' => 'success', 'message' => 'Data inserted successfully'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Failed to insert data'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function fetchAllBreakdown_logsData($page = 1, $records = 10)
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // Fetch data with pagination
                $data = $this->Api_model->fetch_with_paginations('breakdown_logs', 'breakdown_id', 'ASC', $records, $offset);
    
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

    public function fetchAllBreakdown_logsUser_Data($page = 1, $records = 10)
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Get the user ID of the user who inserted the data
               
                $user_id = $user['user_id'];
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;
    
                // If user is admin, fetch all logs
                if ($user['role_id'] ==1) {
                    $data = $this->Api_model->fetch_with_paginations('breakdown_logs', 'breakdown_id', 'ASC', $records, $offset);

                } else {
                   
                    // Fetch data with pagination based on user's ID
                    $data = $this->Api_model->fetch_logs_check_by_user_id('breakdown_logs', 'breakdown_id', 'ASC', $records, $offset, $user_id);
                }
    
                if ($data) {
                    echo json_encode(array('status' => 'success', 'data' => $data));
                } else {
                    echo json_encode(array('status' => 'error', 'message' => 'No data available'));
                }
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Invalid page number or records per page'));
            }
        } elseif ($user === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }
    
    public function fetchNew_Breakdown_logsData()
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // Fetch only the newly inserted breakdown logs
            $data = $this->Api_model->fetchNewBreakdownLogs('breakdown_logs',  'breakdown_id');
            if ($data) {
                echo json_encode(array('status' => 'success', 'data' => $data));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'No new data found'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }
    
    public function fetchNew_Cleaning_logsData()
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // Fetch only the newly inserted breakdown logs
            $data = $this->Api_model->fetchNewBreakdownLogs('cleaning_logs',  'cleaning_id');
            if ($data) {
                echo json_encode(array('status' => 'success', 'data' => $data));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'No new data found'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function fetchNew_Maintenance_logsData()
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // Fetch only the newly inserted breakdown logs
            $data = $this->Api_model->fetchNewBreakdownLogs('maintenance_logs',  'maintenance_id');
            if ($data) {
                echo json_encode(array('status' => 'success', 'data' => $data));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'No new data found'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function fetchNew_Usage_logsData()
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1,2]);
        if ($token_data) {
            // Fetch only the newly inserted breakdown logs
            $data = $this->Api_model->fetchNewBreakdownLogs('usage_logs',  'log_id');
            if ($data) {
                echo json_encode(array('status' => 'success', 'data' => $data));
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'No new data found'));
            }
        } elseif ($token_data === false) {
            // Token is invalid
            echo json_encode(array('status' => 'error', 'message' => 'Invalid Token'));
        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }


    public function fetch_All_logs_Data($page = 1, $records = 10)
    {
        // Check if token is valid
        $token_data = $this->authUserToken([1]);
        if ($token_data) {
            // Check if page number and records per page are provided in the URL
            if (is_numeric($page) && $page > 0 && is_numeric($records) && $records > 0) {
                // Calculate the offset based on page number and records per page
                $offset = ($page - 1) * $records;

                // Fetch data from breakdown_logs table
                $breakdown_logs = $this->Api_model->fetch_with_paginations('breakdown_logs', 'created_at', 'ASC', $records, $offset);
                $breakdown_logs = $this->renameTimeColumns($breakdown_logs, 'break');

                // Fetch data from cleaning_logs table
                $cleaning_logs = $this->Api_model->fetch_with_paginations('cleaning_logs', 'created_at', 'ASC', $records, $offset);
                $cleaning_logs = $this->renameTimeColumns($cleaning_logs, 'cl');

                // Fetch data from maintenance_logs table
                $maintenance_logs = $this->Api_model->fetch_with_paginations('maintenance_logs', 'created_at', 'ASC', $records, $offset);
                $maintenance_logs = $this->renameTimeColumns($maintenance_logs, 'm');

                // Fetch data from usage_logs table
                $usage_logs = $this->Api_model->fetch_with_paginations('usage_logs', 'created_at', 'ASC', $records, $offset);
                $usage_logs = $this->renameTimeColumns($usage_logs, 'op');

                // Merge and sort the results from all tables
                $data = array_merge($breakdown_logs, $cleaning_logs, $maintenance_logs, $usage_logs);
                usort($data, function($a, $b) {
                    return strtotime($a['created_at']) - strtotime($b['created_at']);
                });

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

    private function renameTimeColumns($logs, $prefix)
    {
        foreach ($logs as &$log) {
            $log['st_time'] = $log[$prefix.'_st_time'];
            $log['ed_time'] = $log[$prefix.'_ed_time'];
            unset($log[$prefix.'_st_time']);
            unset($log[$prefix.'_ed_time']);
        }
        return $logs;
    }


    private function _month_num_fix($month){
        // " to convert single month digit into two digit, if month number less then 10 then it append 0 in the start else return the 
        // number as it is "
        if (strlen($month) == 1) {
            return '0' . $month;
        } else {
            return $month;
        }
    }

    public function usage_log_for_machine()
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user && ($user['is_admin']==1 || $user['is_super_admin']==1) ) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            $year = $_POST['year'];
            $month = $this->_month_num_fix($_POST['month']);
            $machine_id = $_POST['machine_id'];
            $start_end_both = $_POST['start_end_both'];

            if ($start_end_both == 'start' || $start_end_both == 'end' || $start_end_both == 'both'){

                if($start_end_both == 'start'){
                    $addon_query = " op_st_time LIKE '{$year}-{$month}%' ";
                } else if($start_end_both == 'end'){
                    $addon_query = " op_ed_time LIKE '{$year}-{$month}%' ";
                } else{
                    $addon_query = " op_st_time LIKE '{$year}-{$month}%' AND op_ed_time LIKE '{$year}-{$month}%'";
                }
    
                $query = " SELECT DATE(ul.date) as usage_data, ul.shift, product, batch, op_st_time, op_ed_time, OpDoneBy.user_name as operation_done_by, 
                    OpCheckBy.user_name as operation_check_by, ul.remarks as operation_remark FROM `usage_logs` ul 
                    LEFT JOIN users OpDoneBy on OpDoneBy.user_id = ul.done_by 
                    LEFT JOIN users OpCheckBy on OpCheckBy.user_id = ul.check_by 
                    INNER JOIN machines m on m.machine_id = ul.machine
                    WHERE ul.machine = {$machine_id} AND {$addon_query} ";
                $data = $this->Api_model->raw_query_arr($query);
                echo json_encode(array('status' => 'success', 'message'=>'ok', 'data'=>$data));
            } else { echo json_encode(array('status' => 'error', 'message' => "value for key 'start_end_both' should be 'start', 'end' or 'both'!")); }

        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function usage_log_for_month()
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user && ($user['is_admin']==1 || $user['is_super_admin']==1) ) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            $year = $_POST['year'];
            $month = $this->_month_num_fix($_POST['month']);
            $start_end_both = $_POST['start_end_both'];
            if ($start_end_both == 'start' || $start_end_both == 'end' || $start_end_both == 'both'){

                if($start_end_both == 'start'){
                    $addon_query = " op_st_time LIKE '{$year}-{$month}%' ";
                } else if($start_end_both == 'end'){
                    $addon_query = " op_ed_time LIKE '{$year}-{$month}%' ";
                } else{
                    $addon_query = " op_st_time LIKE '{$year}-{$month}%' AND op_ed_time LIKE '{$year}-{$month}%'";
                }
    
                $query = "SELECT DATE(ul.date) as usage_data, ul.shift, m.machine_name as machine_name, product, batch, op_st_time, op_ed_time, OpDoneBy.user_name as operation_done_by, 
                    OpCheckBy.user_name as operation_check_by, ul.remarks as operation_remark FROM `usage_logs` ul 
                    LEFT JOIN users OpDoneBy on OpDoneBy.user_id = ul.done_by 
                    LEFT JOIN users OpCheckBy on OpCheckBy.user_id = ul.check_by 
                    INNER JOIN machines m on m.machine_id = ul.machine
                    WHERE {$addon_query} ";
                $data = $this->Api_model->raw_query_arr($query);
                echo json_encode(array('status' => 'success', 'message'=>'ok', 'data'=>$data));
            } else { echo json_encode(array('status' => 'error', 'message' => "value for key 'start_end_both' should be 'start', 'end' or 'both'!")); }

        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function cleaning_log_for_machine()
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user && ($user['is_admin']==1 || $user['is_super_admin']==1) ) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            $year = $_POST['year'];
            $month = $this->_month_num_fix($_POST['month']);
            $machine_id = $_POST['machine_id'];
            $start_end_both = $_POST['start_end_both'];
            if ($start_end_both == 'start' || $start_end_both == 'end' || $start_end_both == 'both'){

                if($start_end_both == 'start'){
                    $addon_query = " cl_st_time LIKE '{$year}-{$month}%' ";
                } else if($start_end_both == 'end'){
                    $addon_query = " cl_ed_time LIKE '{$year}-{$month}%' ";
                } else{
                    $addon_query = " cl_st_time LIKE '{$year}-{$month}%' AND cl_ed_time LIKE '{$year}-{$month}%'";
                }
    
                $query = "SELECT DATE(cl.date) as cleaning_data, cl.shift, ul.product, ul.batch, cl_st_time, cl_ed_time, ClDoneBy.user_name as cleaning_done_by, ClCheckBy.user_name as cleaning_check_by, cl.remarks as cleaning_remark FROM `cleaning_logs` cl 
                    LEFT JOIN usage_logs ul ON ul.log_id = cl.usage_log_id 
                    LEFT JOIN users ClDoneBy on ClDoneBy.user_id = cl.done_by 
                    LEFT JOIN users ClCheckBy on ClCheckBy.user_id = cl.check_by
                    INNER JOIN machines m on m.machine_id = ul.machine
                    WHERE ul.machine = {$machine_id} AND {$addon_query} ";
                $data = $this->Api_model->raw_query_arr($query);
                echo json_encode(array('status' => 'success', 'message'=>'ok', 'data'=>$data));
            } else { echo json_encode(array('status' => 'error', 'message' => "value for key 'start_end_both' should be 'start', 'end' or 'both'!")); }

        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }

    public function cleaning_log_for_month()
    {
        // Check if token is valid
        $user = $this->authUserToken([1,2]);
        if ($user && ($user['is_admin']==1 || $user['is_super_admin']==1) ) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            
            $year = $_POST['year'];
            $month = $this->_month_num_fix($_POST['month']);
            $start_end_both = $_POST['start_end_both'];
            if ($start_end_both == 'start' || $start_end_both == 'end' || $start_end_both == 'both'){

                if($start_end_both == 'start'){
                    $addon_query = " cl_st_time LIKE '{$year}-{$month}%' ";
                } else if($start_end_both == 'end'){
                    $addon_query = " cl_ed_time LIKE '{$year}-{$month}%' ";
                } else{
                    $addon_query = " cl_st_time LIKE '{$year}-{$month}%' AND cl_ed_time LIKE '{$year}-{$month}%'";
                }
    
                $query = "SELECT DATE(cl.date) as cleaning_data, cl.shift, m.machine_name as machine_name, ul.product, ul.batch, cl_st_time, cl_ed_time, ClDoneBy.user_name as cleaning_done_by, ClCheckBy.user_name as cleaning_check_by, cl.remarks as cleaning_remark FROM `cleaning_logs` cl 
                    LEFT JOIN usage_logs ul ON ul.log_id = cl.usage_log_id 
                    LEFT JOIN users ClDoneBy on ClDoneBy.user_id = cl.done_by 
                    LEFT JOIN users ClCheckBy on ClCheckBy.user_id = cl.check_by
                    INNER JOIN machines m on m.machine_id = ul.machine
                    WHERE {$addon_query} ";
                $data = $this->Api_model->raw_query_arr($query);
                echo json_encode(array('status' => 'success', 'message'=>'ok', 'data'=>$data));
            } else { echo json_encode(array('status' => 'error', 'message' => "value for key 'start_end_both' should be 'start', 'end' or 'both'!")); }

        } else {
            // User not logged in
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
        }
    }  


}

  
            


   


