<?php

class DbOperation
{
    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect();
        $this->con = $db->connect();
require($_SERVER['DOCUMENT_ROOT'].'/sharespacewp/wp-load.php');
    }
    //Method to register a new student
    public function createStudent($fname,$lname,$add1,$add2,$city,$state,$country,$zip,$phone, $email, $password){
        $register_st_add1=$add1;
        $register_st_add2 = $add2;
        $register_city = $city;
        $register_state = $state;
        $register_country = $country;
        $zip_code = $zip;
        $register_phone = $phone;
        if (!email_exists($email)) {
            $apikey = $this->generateApiKey();
             $user_id = wp_insert_user(
                                         array(
                                                'user_login'    =>  $email,
                                                'user_pass' =>  $pass,
                                                'first_name'    =>  $fname,
                                                'last_name' =>  $lname,
                                                'user_email'    =>  $email,
                                                'display_name'  =>  $fname . ' ' . $lname,
                                                'nickname'  =>  $fname . ' ' . $lname,
                                                'role'      =>  'None'
                                            )
                                     );
             $code = sha1( $user_id . time() );
        $activation_link = add_query_arg( array( 'key' => $code, 'user' => $user_id ), get_permalink(66));//66 is the slug of the Activation page.
         add_user_meta( $user_id, 'has_to_be_activated', $code, true );
        update_user_meta( $user_id, 'register_st_add1', $register_st_add1 );
        update_user_meta( $user_id, 'register_st_add2', $register_st_add2 );
        update_user_meta( $user_id, 'register_city', $register_city );
        update_user_meta( $user_id, 'register_state', $register_state );
        update_user_meta( $user_id, 'zip_code', $zip_code );
        update_user_meta( $user_id, 'register_country', $register_country );
        update_user_meta( $user_id, 'register_phone', $register_phone );
 
            // $stmt->close();
            if ($user_id) {
                
                 $link = $activation_link;
                
                 return $link;
            } else {
                return 1;
            }
        } else {
            return 2;
        }
    }

    //Method to let a student log in
    // public function studentLogin($username,$pass){
    //     $password = md5($pass);
    //     $stmt = $this->con->prepare("SELECT * FROM students WHERE username=? and password=?");
    //     $stmt->bind_param("ss",$username,$password);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     $num_rows = $stmt->num_rows;
    //     $stmt->close();
    //     return $num_rows>0;
    // }

public function studentLogin($username,$pass){
        global $wpdb;
          $hasher = new PasswordHash( 8, true );
          $upass = $hasher->HashPassword( wp_unslash($pass) );
        $login_data = array();
        $login_data['user_login'] = $username;
        $login_data['user_password'] = $upass;
        $login_data['remember'] = true;
        $results = $wpdb->get_row( "SELECT ID FROM wp_users WHERE user_email='".$username."'");
        $activation_id = $results->ID;
        $activation_key =  get_user_meta( $activation_id, 'has_to_be_activated', true );
         
            if($activation_key != false ){
                return 2;//if activation key exists than show the error
             }
             else{
                    $user_verify = wp_signon( $login_data, false ); 
                 
                    if ( is_wp_error($user_verify) ) 
                    {
                        
                      return 1; //show invalid username and password.

                     }
                      else {    
                        return 0; //login success.
                       exit();
                     }
               }


         
    }

    //method to register a new facultly
    public function createFaculty($name,$username,$pass,$subject){
        if (!$this->isFacultyExists($username)) {
            $password = md5($pass);
            $apikey = $this->generateApiKey();
            $stmt = $this->con->prepare("INSERT INTO faculties(name, username, password, subject, api_key) values(?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $username, $password, $subject, $apikey);
            $result = $stmt->execute();
            $stmt->close();
            if ($result) {
                return 0;
            } else {
                return 1;
            }
        } else {
            return 2;
        }
    }

    //method to let a faculty log in
    public function facultyLogin($username, $pass){
        $password = md5($pass);
        $stmt = $this->con->prepare("SELECT * FROM faculties WHERE username=? and password =?");
        $stmt->bind_param("ss",$username,$password);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows>0;
    }

    //Method to create a new assignment
    public function createAssignment($name,$detail,$facultyid,$studentid){
        $stmt = $this->con->prepare("INSERT INTO assignments (name,details,faculties_id,students_id) VALUES (?,?,?,?)");
        $stmt->bind_param("ssii",$name,$detail,$facultyid,$studentid);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
            return true;
        }
        return false;
    }

    //Method to update assignment status
    public function updateAssignment($id){
        $stmt = $this->con->prepare("UPDATE assignments SET completed = 1 WHERE id=?");
        $stmt->bind_param("i",$id);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
            return true;
        }
        return false;
    }

    //Method to get all the assignments of a particular student
    public function getAssignments($studentid){
        $stmt = $this->con->prepare("SELECT * FROM assignments WHERE students_id=?");
        $stmt->bind_param("i",$studentid);
        $stmt->execute();
        $assignments = $stmt->get_result();
        $stmt->close();
        return $assignments;
    }

    //Method to get student details
    public function getStudent($username){
  global $wpdb;
       $student = $wpdb->get_results( "SELECT * FROM wp_users WHERE user_email='".$username."'");
        return $student;
    }

    //Method to fetch all students from database
    public function getAllStudents(){
        $stmt = $this->con->prepare("SELECT * FROM students");
        $stmt->execute();
        $students = $stmt->get_result();
        $stmt->close();
        return $students;
    }

    //Method to get faculy details by username
    public function getFaculty($username){
        $stmt = $this->con->prepare("SELECT * FROM faculties WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        $faculty = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $faculty;
    }

    //Method to get faculty name by id
    public function getFacultyName($id){
        $stmt = $this->con->prepare("SELECT name FROM faculties WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $faculty = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $faculty['name'];
    }

    //Method to check the student username already exist or not
    private function isStudentExists($username) {
        $stmt = $this->con->prepare("SELECT id from students WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    //Method to check the faculty username already exist or not
    private function isFacultyExists($username) {
        $stmt = $this->con->prepare("SELECT id from faculties WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    //Checking the student is valid or not by api key
    public function isValidStudent($api_key) {
        $stmt = $this->con->prepare("SELECT id from students WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    //Checking the faculty is valid or not by api key
    public function isValidFaculty($api_key){
        $stmt = $this->con->prepare("SELECT id from faculties WHERE api_key=?");
        $stmt->bind_param("s",$api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows>0;
    }

    //Method to generate a unique api key every time
    private function generateApiKey(){
        return md5(uniqid(rand(), true));
    }
}