<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_model extends CI_Model {

    /**
     * This function is used to get the user listing count
     * @param string $searchText : This is optional search text
     * @return number $count : This is row count
     */
    function userListingCount($searchText = '') {
        $this->db->select('BaseTbl.userId, BaseTbl.email, BaseTbl.name, BaseTbl.mobile, Role.role');
        $this->db->from('tbl_users as BaseTbl');
        $this->db->join('tbl_roles as Role', 'Role.roleId = BaseTbl.roleId', 'left');
        if (!empty($searchText)) {
            $likeCriteria = "(BaseTbl.email  LIKE '%" . $searchText . "%'
                            OR  BaseTbl.name  LIKE '%" . $searchText . "%'
                            OR  BaseTbl.mobile  LIKE '%" . $searchText . "%')";
            $this->db->where($likeCriteria);
        }
        $this->db->where('BaseTbl.isDeleted', 0);
        $this->db->where('BaseTbl.roleId !=', 1);
        $query = $this->db->get();

        return count($query->result());
    }

    /**
     * This function is used to get the user listing count
     * @param string $searchText : This is optional search text
     * @param number $page : This is pagination offset
     * @param number $segment : This is pagination limit
     * @return array $result : This is result
     */
    function userListing($searchText = '', $page, $segment) {
        $this->db->select('BaseTbl.userId, BaseTbl.email, BaseTbl.name, BaseTbl.mobile, Role.role');
        $this->db->from('tbl_users as BaseTbl');
        $this->db->join('tbl_roles as Role', 'Role.roleId = BaseTbl.roleId', 'left');
        if (!empty($searchText)) {
            $likeCriteria = "(BaseTbl.email  LIKE '%" . $searchText . "%'
                            OR  BaseTbl.name  LIKE '%" . $searchText . "%'
                            OR  BaseTbl.mobile  LIKE '%" . $searchText . "%')";
            $this->db->where($likeCriteria);
        }
        $this->db->where('BaseTbl.isDeleted', 0);
        $this->db->where('BaseTbl.roleId !=', 1);
        $this->db->limit($page, $segment);
        $query = $this->db->get();

        $result = $query->result();
        return $result;
    }

    /**
     * This function is used to get the user roles information
     * @return array $result : This is result of the query
     */
    function getUserRoles() {
        $this->db->select('roleId, role');
        $this->db->from('tbl_roles');
        $this->db->where('roleId !=', 1);
        $query = $this->db->get();

        return $query->result();
    }

    /**
     * This function is used to check whether email id is already exist or not
     * @param {string} $email : This is email id
     * @param {number} $userId : This is user id
     * @return {mixed} $result : This is searched result
     */
    function checkEmailExists($email, $userId = 0) {
        $this->db->select("email");
        $this->db->from("tbl_users");
        $this->db->where("email", $email);
        $this->db->where("isDeleted", 0);
        if ($userId != 0) {
            $this->db->where("userId !=", $userId);
        }
        $query = $this->db->get();

        return $query->result();
    }

    /**
     * This function is used to add new user to system
     * @return number $insert_id : This is last inserted id
     */
    function addNewUser($userInfo) {
        $this->db->trans_start();
        $this->db->insert('tbl_users', $userInfo);
        $insert_id = $this->db->insert_id();
        $this->db->trans_complete();
        return $insert_id;
    }

    /**
     * This function is used to feature characteristics of donor to system
     * @return number $insert_id : This is last inserted id
     */
    function examineDonor($examineInfo) {
        $this->db->trans_start();
        $this->db->insert('tbl_donors_preexam', $examineInfo);
        $insert_id = $this->db->insert_id();
        $this->db->trans_complete();
        return $insert_id;
    }

    /**
     * This function used to get user information by id
     * @param number $userId : This is user id
     * @return array $result : This is user information
     */
    function getUserInfo($userId) {
        $this->db->select('userId, name, email, mobile, roleId');
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', 0);
        $this->db->where('roleId !=', 1);
        $this->db->where('userId', $userId);
        $query = $this->db->get();

        return $query->result();
    }

    /**
     * This function is used to update the user information
     * @param array $userInfo : This is users updated information
     * @param number $userId : This is user id
     */
    function editUser($userInfo, $userId) {
        $this->db->where('userId', $userId);
        $this->db->update('tbl_users', $userInfo);

        return TRUE;
    }

    /**
     * This function is used to delete the user information
     * @param number $userId : This is user id
     * @return boolean $result : TRUE / FALSE
     */
    function deleteUser($userId, $userInfo) {
        $this->db->where('userId', $userId);
        $this->db->update('tbl_users', $userInfo);

        return $this->db->affected_rows();
    }

    /**
     * This function is used to match users password for change password
     * @param number $userId : This is user id
     */
    function matchOldPassword($userId, $oldPassword) {
        $this->db->select('userId, password');
        $this->db->where('userId', $userId);
        $this->db->where('isDeleted', 0);
        $query = $this->db->get('tbl_users');

        $user = $query->result();

        if (!empty($user)) {
            if (verifyHashedPassword($oldPassword, $user[0]->password)) {
                return $user;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    /**
     * This function is used to change users password
     * @param number $userId : This is user id
     * @param array $userInfo : This is user updation info
     */
    function changePassword($userId, $userInfo) {
        $this->db->where('userId', $userId);
        $this->db->where('isDeleted', 0);
        $this->db->update('tbl_users', $userInfo);

        return $this->db->affected_rows();
    }

    /**
      this function retrives all donors from database
     * */
    function getAllDonors() {
        /**
         *  All queries relating to data donors
         * */
        $this->db->select('BaseTbl.userId, BaseTbl.email, BaseTbl.name, BaseTbl.mobile, tbl_donors_preexam.blood_type');
        $this->db->from('tbl_users as BaseTbl');
        $this->db->join('tbl_donors_preexam', 'tbl_donors_preexam.userid = BaseTbl.userid', 'left');
        $this->db->join('tbl_roles as Role', 'Role.roleid=BaseTbl.roleid');
        $this->db->where('isDeleted', 0);
        $this->db->where('don_status', 1);
        $this->db->where('Role.roleId', 3);
        $query = $this->db->get();
        $result = $query->result();
        return $result;
    }

    /*
     * getNextPropableDonors
     */

    function getNextProbableDonors() {
        $this->db->select('BaseTbl.userid, BaseTbl.name, BaseTbl.email,tbl_donation.nextSafeDonation');
        $this->db->from('tbl_users as BaseTbl');
        $this->db->join('tbl_donation as tbl_donation', 'tbl_donation.userid = BaseTbl.userid', 'left');
        $this->db->where('BaseTbl.isDeleted', 0);
        $this->db->where('BaseTbl.don_status', 0);
        $query = $this->db->get();
        $result = $query->result();
        if ($result) {
            return $result;
        } else {

            return FALSE;
        }
    }

    /*
     * update donation status of a specific userid
     */

    function updateDonationStatus() {

        $query = 'update tbl_donation join tbl_users ON tbl_donation.userid=tbl_users.userid '
                . 'SET tbl_users.don_status=1 where tbl_donation.nextSafeDonation-date("Y-m-d")<= 0 '
                . 'AND tbl_users.isDeleted =0';

        if ($query) {
            $this->db->query($query);
            return TRUE;
        } else {
            $this->db->query($query);
            return FALSE;
        }
    }

    /*
     * function unregistered users
     */

    function unregistered() {
        $this->db->select('tbl_contact.*');
        $this->db->where('status', 'unread');
        $this->db->where('once_read', 0);
        $this->db->from('tbl_contact');
        $query = $this->db->get();
        $result = $query->result();
        if ($result) {
            return $result;
        } else {

            return FALSE;
        }
    }

    /*
     * count notification from contactForm
     */

    function getContactFormNotification() {
        $q = $this->db->query("SELECT COUNT(*) as count_rows FROM tbl_contact where status='unread'");
        return $q->row_array();
    }

    /*
     * select textArea
     */

    function textArea($userid) {
        $this->db->select('textArea');
        $this->db->where('contact_id', $userid);
        $this->db->where('status', 'unread');
        $this->db->where('once_read', 0);
        $this->db->from('tbl_contact');
        $query = $this->db->get();
        $result = $query->result();
        if ($result) {
            return $result;
        } else {

            return FALSE;
        }
    }
    /*
     * read users contact 
     */
    function contact_users_readmsg()
    {
       $this->db->select('tbl_contact.*');
        $this->db->where('status', 'read');
        $this->db->where('once_read', 1);
        $this->db->from('tbl_contact');
        $query = $this->db->get();
        $result = $query->result();
        if ($result) {
            return $result;
        } else {

            return FALSE;
        }  
    }

}
