<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . '/libraries/BaseController.php';

class User extends BaseController {

    /**
     * This is default constructor of the class
     */
    public function __construct() {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('task_model');
        $this->load->model('login_model');
        $this->isLoggedIn();
    }

    /**
     * This function used to load the first screen of the user
     */
    public function index() {
        $this->global['pageTitle'] = 'BloodDonor : Dashboard';

        $data['countDonors'] = $this->task_model->countDonors();
        $data['countAllUsers'] = $this->task_model->getCountAllUsers();
        $data['specificNextSafeD'] = $this->task_model->specificNextSafeDonation();
        $data['getmales'] = $this->task_model->getMales();
        $data['getfemales'] = $this->task_model->getFemales();
        $this->loadViews("dashboard", $this->global, $data, NULL);
    }

    /**
     * This function is used to load the user list
     */
    function userListing() {
        if ($this->isAdmin() == TRUE) {
            $this->loadThis();
        } else {
            $searchText = $this->input->post('searchText');
            $data['searchText'] = $searchText;
            $this->load->library('pagination');

            $count = $this->user_model->userListingCount($searchText);

            $returns = $this->paginationCompress("userListing/", $count, 5);

            $data['userRecords'] = $this->user_model->userListing($searchText, $returns["page"], $returns["segment"]);

            $this->global['pageTitle'] = 'BloodDonor : User Listing';
            $data['countAllUsers'] = $this->task_model->getCountAllUsers(); //List count users
            $this->loadViews("users", $this->global, $data, NULL);
        }
    }

    /**
     * This function is used to load the add new form
     */
    function addNew() {
        if ($this->isAdmin() == TRUE) {
            $this->loadThis();
        } else {
            $data['roles'] = $this->user_model->getUserRoles();

            $this->global['pageTitle'] = 'BloodDonor : Add New User';

            $this->loadViews("addNew", $this->global, $data, NULL);
        }
    }

    /**
     * This function is used to check whether email already exist or not
     */
    function checkEmailExists() {
        $userId = $this->input->post("userId");
        $email = $this->input->post("email");

        if (empty($userId)) {
            $result = $this->user_model->checkEmailExists($email);
        } else {
            $result = $this->user_model->checkEmailExists($email, $userId);
        }

        if (empty($result)) {
            echo("true");
        } else {
            echo("false");
        }
    }

    /**
     * This function is used to add new user to the system
     */
    function addNewUser() {
        if ($this->isAdmin() == TRUE) {
            $this->loadThis();
        } else {
            $this->load->library('form_validation');

            $this->form_validation->set_rules('fname', 'Full Name', 'trim|required|max_length[128]|xss_clean');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean|max_length[128]');
            $this->form_validation->set_rules('mobile', 'Mobile Number', 'required|min_length[9]|xss_clean');
            $this->form_validation->set_rules('gender', 'Gender', 'trim|required');
            $this->form_validation->set_rules('weightLBS', 'WeightLBS', 'trim|required');
            $this->form_validation->set_rules('temperature', 'Temperature', 'trim|required');
            $this->form_validation->set_rules('blood_pressure', 'Blood Pressure', 'trim|required');
            $this->form_validation->set_rules('blood_type', 'Blood Type', 'trim|required');
            $this->form_validation->set_rules('dateOfBirth', 'Date Of Birth', 'trim|required');
            $this->form_validation->set_rules('password', 'Password', 'required|max_length[20]|min_length[6]');
            $this->form_validation->set_rules('cpassword', 'Confirm Password', 'trim|required|matches[password]|max_length[6]|min_length[6]');
            $this->form_validation->set_rules('role', 'Role', 'trim|required|numeric');

            if ($this->form_validation->run() == FALSE) {
                $this->addNew();
            } else {
                $name = ucwords(strtolower($this->input->post('fname')));
                $email = $this->input->post('email');
                $password = $this->input->post('password');
                $roleId = $this->input->post('role');
                $mobile = $this->input->post('code') . $this->input->post('mobile');

                $userInfo = array('email' => $email, 'password' => getHashedPassword($password), 'roleId' => $roleId, 'name' => $name,
                    'mobile' => $mobile, 'createdBy' => $this->vendorId, 'createdDtm' => date('Y-m-d H:i:sa'));

                $result = $this->user_model->addNewUser($userInfo);

                /**
                 * ExamineDonor Array
                 * */
                $examineInfo = array(
                    'userid' => $result,
                    'gender' => $this->input->post('gender'),
                    'weightLBS' => $this->input->post('weightLBS'),
                    'temperature' => $this->input->post('temperature'),
                    'blood_pressure' => $this->input->post('blood_pressure'),
                    'blood_type' => $this->input->post('blood_type'),
                    'dateOfBirth' => date('Y-m-d', strtotime(str_replace('-', '/', $this->input->post('dateOfBirth')))));

                $resul = $this->user_model->examineDonor($examineInfo);
                if ($result && $resul > 0) {
                    $this->session->set_flashdata('success', 'New User Created Successfully');
                } else {
                    $this->session->set_flashdata('error', 'User Creation Failed');
                }

                redirect('addNew');
            }
        }
    }

    /**
     * This function is used load user edit information
     * @param number $userId : Optional : This is user id
     */
    function editOld($userId = NULL) {
        if ($this->isAdmin() == TRUE || $userId == 1) {
            $this->loadThis();
        } else {
            if ($userId == null) {
                redirect('userListing');
            }

            $data['roles'] = $this->user_model->getUserRoles();
            $data['userInfo'] = $this->user_model->getUserInfo($userId);

            $this->global['pageTitle'] = 'BloodDonor : Edit User';

            $this->loadViews("editOld", $this->global, $data, NULL);
        }
    }

    /**
     * This function is used to edit the user information
     */
    function editUser() {
        if ($this->isAdmin() == TRUE) {
            $this->loadThis();
        } else {
            $this->load->library('form_validation');

            $userId = $this->input->post('userId');

            $this->form_validation->set_rules('fname', 'Full Name', 'trim|required|max_length[128]|xss_clean');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean|max_length[128]');
            $this->form_validation->set_rules('password', 'Password', 'matches[cpassword]|max_length[20]|min_length[6]');
            $this->form_validation->set_rules('cpassword', 'Confirm Password', 'matches[password]|max_length[20]|min_length[6]');
            $this->form_validation->set_rules('role', 'Role', 'trim|required|numeric');
            $this->form_validation->set_rules('mobile', 'Mobile Number', 'required|min_length[9]|xss_clean');

            if ($this->form_validation->run() == FALSE) {
                $this->editOld($userId);
            } else {
                $name = ucwords(strtolower($this->input->post('fname')));
                $email = $this->input->post('email');
                $password = $this->input->post('password');
                $roleId = $this->input->post('role');
                $mobile = $this->input->post('code') . $this->input->post('mobile');

                $userInfo = array();

                if (empty($password)) {
                    $userInfo = array('email' => $email, 'roleId' => $roleId, 'name' => $name,
                        'mobile' => $mobile, 'updatedBy' => $this->vendorId, 'updatedDtm' => date('Y-m-d H:i:sa'));
                } else {
                    $userInfo = array('email' => $email, 'password' => getHashedPassword($password), 'roleId' => $roleId,
                        'name' => ucwords($name), 'mobile' => $mobile, 'updatedBy' => $this->vendorId,
                        'updatedDtm' => date('Y-m-d H:i:sa'));
                }

                $result = $this->user_model->editUser($userInfo, $userId);

                if ($result == true) {
                    $this->session->set_flashdata('success', 'User Updated Successfully');
                } else {
                    $this->session->set_flashdata('error', 'User Updation Failed');
                }

                redirect('userListing');
            }
        }
    }

    /**
     * This function is used to delete the user using userId
     * @return boolean $result : TRUE / FALSE
     */
    function deleteUser() {
        if ($this->isAdmin() == TRUE) {
            echo(json_encode(array('status' => 'access')));
        } else {
            $userId = $this->input->post('userId');
            $userInfo = array('isDeleted' => 1, 'updatedBy' => $this->vendorId, 'updatedDtm' => date('Y-m-d H:i:sa'));

            $result = $this->user_model->deleteUser($userId, $userInfo);

            if ($result > 0) {
                echo(json_encode(array('status' => TRUE)));
            } else {
                echo(json_encode(array('status' => FALSE)));
            }
        }
    }

    /**
     * This function is used to load the change password screen
     */
    function loadChangePass() {
        $this->global['pageTitle'] = 'BloodDonor : Change Password';

        $this->loadViews("changePassword", $this->global, NULL, NULL);
    }

    /**
     * This function is used to change the password of the user
     */
    function changePassword() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('oldPassword', 'Old password', 'required|max_length[20]');
        $this->form_validation->set_rules('newPassword', 'New password', 'required|max_length[20]|min_length[6]');
        $this->form_validation->set_rules('cNewPassword', 'Confirm new password', 'required|matches[newPassword]|max_length[20]|min_length[6]');
        if ($this->form_validation->run() == FALSE) {
            $this->loadChangePass();
        } else {
            $oldPassword = $this->input->post('oldPassword');
            $newPassword = $this->input->post('newPassword');
            $resultPas = $this->user_model->matchOldPassword($this->vendorId, $oldPassword);
            if (empty($resultPas)) {
                $this->session->set_flashdata('nomatch', 'Your old password not correct');
                redirect('loadChangePass');
            } else {
                $usersData = array('password' => getHashedPassword($newPassword), 'updatedBy' => $this->vendorId,
                    'updatedDtm' => date('Y-m-d H:i:sa'));
                $result = $this->user_model->changePassword($this->vendorId, $usersData);
                if ($result > 0) {
                    $this->session->set_flashdata('success', 'Password Update Successful');
                } else {
                    $this->session->set_flashdata('error', 'Password updation failed');
                }

                redirect('loadChangePass');
            }
        }
    }

    function pageNotFound() {
        $this->global['pageTitle'] = 'BloodDonor : 404 - Page Not Found';

        $this->loadViews("404", $this->global, NULL, NULL);
    }

    /*
     * function getAllDonors
     */

    function donors() {
        if ($this->isTicketter() == TRUE) {
            $this->loadThis();
        } else {
            $this->global['pageTitle'] = 'BloodDonor : Donors';
            $data['tbl_users'] = $this->user_model->getAllDonors();
            $data['getNextProbableDonors'] = $this->user_model->getNextProbableDonors();
            $this->loadViews("donors", $this->global, $data, NULL);
        }
    }

    /*
     * function unRegistered Users
     */

    function unregistered_users() {
        if ($this->isTicketter() == TRUE) {
            $this->loadThis();
        } else {
            $this->global['pageTitle'] = 'BloodDonor : Unregistered Users';
            $data['unregistered'] = $this->user_model->unregistered();
            $data['read_msg']=  $this->user_model->contact_users_readmsg();
            $data['contact_form'] = $this->user_model->getContactFormNotification();
            $this->loadViews("unRegisteredUsers", $this->global, $data, NULL);
        }
    }

    /*
     * function read message
     */

    function read_msg($val) {
        if ($this->isTicketter() == TRUE) {
            $this->loadThis();
        } else {
            if (isset($_POST['readmsg'])) {
                $this->db->set('status', 'read');
                $this->db->set('once_read', 1);
                $this->db->where('contact_id', $val);
                $this->db->update('tbl_contact');
                redirect('user/unregistered_users');
            }

            $this->global['pageTitle'] = 'BloodDonor : Read Message';
            $data['textArea'] = $this->user_model->textArea($val);
//            $data['codekey'] = $val;
            $this->loadViews("read_msg", $this->global, $data, NULL);
        }
//        redirect('user/unRegisteredUsers');
//        $this->loadViews("unRegisteredUsers", $this->global, NULL, NULL);
    }

}
