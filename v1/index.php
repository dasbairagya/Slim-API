<?php
// reference:https://www.simplifiedcoding.net/php-rest-api-framework-tutorial-part-2/
//including the required files

global $wpdb;
require_once '../include/DbOperation.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

/* *
 * URL: http://localhost/StudentApp/v1/createstudent
 * Parameters: name, username, password
 * Method: POST
 * */
$app->post('/register', function () use ($app) {
    verifyRequiredParams(array('fname','lname', 'add1','add2','city','state','country','zip','phone','email','password'));
    $response = array();
    $fname = $app->request->post('fname');
    $lname = $app->request->post('lname');
    $add1 = $app->request->post('add1');
    $add2 = $app->request->post('add2');
    $city = $app->request->post('city');
    $state = $app->request->post('state');
    $country = $app->request->post('country');
    $zip = $app->request->post('zip');
    $phone = $app->request->post('phone');
    $email = $app->request->post('email');
    $password = $app->request->post('password');
    $db = new DbOperation();
    $res = $db->createStudent($fname,$lname,$add1,$add2,$city,$state,$country,$zip,$phone, $email, $password);
   
    if ($res!=1 && $res!=2) {
        $response["error"] = false;
        $response["message"] = "You are successfully registered";
        $response['link'] = $res;
        echoResponse(201, $response);
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while registereing";
        echoResponse(200, $response);
    } else if ($res == 2) {
        $response["error"] = true;
        $response["message"] = "Sorry, this student  already existed";
        echoResponse(200, $response);
    }
});
$app->post('/test', function () use ($app) {
     global $wpdb;
        $db = new DbOperation();
    $response = array();
 $username = $app->request->post('username');
 $pass = $app->request->post('password');
  $student = $db->getStudent($username);
            $hasher = new PasswordHash( 8, true );
          $upass = $hasher->HashPassword( wp_unslash($pass) );

          $response['results'] = $student;
          $response['username'] = $username;
          $response['password'] = $upass;
          


           echoResponse(200, $response);


});



/* *
 * URL: http://localhost/StudentApp/v1/studentlogin
 * Parameters: username, password
 * Method: POST
 * */
$app->post('/login', function () use ($app) {
   global $wpdb;
    verifyRequiredParams(array('username', 'password'));
    $username = $app->request->post('username');
    $password = $app->request->post('password');

 
    $db = new DbOperation();
    $response = array();
    $results = $db->studentLogin($username, $password);

    if ($results== 0) {

        $student = $db->getStudent($username);
        // var_dump($student);
        $response['error'] = false;
        $response['id'] = $student[0]->ID;
        $response['name'] = $student[0]->display_name;
        $response['username'] = $student[0]->user_email;
        $response['results'] = $results;
        // $response['apikey'] = $student['api_key'];
    } else {
        if($results == 1){
        $response['error'] = true;
        $response['message'] = "Invalid username or password";
        $response['results'] = $results;
        }
        else{
            $response['message'] ="Your account has not been activated yet.
             To activate it check your email and clik on the activation link.";
             $response['results'] = $results;
            }
    }
    echoResponse(200, $response);
});

/* *
 * URL: http://localhost/StudentApp/v1/createfaculty
 * Parameters: name, username, password, subject
 * Method: POST
 * */
$app->post('/createfaculty', function () use ($app) {
    verifyRequiredParams(array('name', 'username', 'password', 'subject'));
    $name = $app->request->post('name');
    $username = $app->request->post('username');
    $password = $app->request->post('password');
    $subject = $app->request->post('subject');

    $db = new DbOperation();
    $response = array();

    $res = $db->createFaculty($name, $username, $password, $subject);
    if ($res == 0) {
        $response["error"] = false;
        $response["message"] = "You are successfully registered";
        echoResponse(201, $response);
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while registereing";
        echoResponse(200, $response);
    } else if ($res == 2) {
        $response["error"] = true;
        $response["message"] = "Sorry, this faculty already existed";
        echoResponse(200, $response);
    }
});


/* *
 * URL: http://localhost/StudentApp/v1/facultylogin
 * Parameters: username, password
 * Method: POST
 * */

$app->post('/facultylogin', function() use ($app){
    verifyRequiredParams(array('username','password'));
    $username = $app->request->post('username');
    $password = $app->request->post('password');

    $db = new DbOperation();

    $response = array();

    if($db->facultyLogin($username,$password)){
        $faculty = $db->getFaculty($username);
        $response['error'] = false;
        $response['id'] = $faculty['id'];
        $response['name'] = $faculty['name'];
        $response['username'] = $faculty['username'];
        $response['subject'] = $faculty['subject'];
        $response['apikey'] = $faculty['api_key'];
    }else{
        $response['error'] = true;
        $response['message'] = "Invalid username or password";
    }

    echoResponse(200,$response);
});


/* *
 * URL: http://localhost/StudentApp/v1/createassignment
 * Parameters: name, details, facultyid, studentid
 * Method: POST
 * */
$app->post('/createassignment',function() use ($app){
    verifyRequiredParams(array('name','details','facultyid','studentid'));

    $name = $app->request->post('name');
    $details = $app->request->post('details');
    $facultyid = $app->request->post('facultyid');
    $studentid = $app->request->post('studentid');

    $db = new DbOperation();

    $response = array();

    if($db->createAssignment($name,$details,$facultyid,$studentid)){
        $response['error'] = false;
        $response['message'] = "Assignment created successfully";
    }else{
        $response['error'] = true;
        $response['message'] = "Could not create assignment";
    }

    echoResponse(200,$response);

});

/* *
 * URL: http://localhost/StudentApp/v1/assignments/<student_id>
 * Parameters: none
 * Authorization: Put API Key in Request Header
 * Method: GET
 * */
$app->get('/assignments/:id', 'authenticateStudent', function($student_id) use ($app){
    $db = new DbOperation();
    $result = $db->getAssignments($student_id);
    $response = array();
    $response['error'] = false;
    $response['assignments'] = array();
    while($row = $result->fetch_assoc()){
        $temp = array();
        $temp['id']=$row['id'];
        $temp['name'] = $row['name'];
        $temp['details'] = $row['details'];
        $temp['completed'] = $row['completed'];
        $temp['faculty']= $db->getFacultyName($row['faculties_id']);
        array_push($response['assignments'],$temp);
    }
    echoResponse(200,$response);
});


/* *
 * URL: http://localhost/StudentApp/v1/submitassignment/<assignment_id>
 * Parameters: none
 * Authorization: Put API Key in Request Header
 * Method: PUT
 * */

$app->put('/submitassignment/:id', 'authenticateFaculty', function($assignment_id) use ($app){
    $db = new DbOperation();
    $result = $db->updateAssignment($assignment_id);
    $response = array();
    if($result){
        $response['error'] = false;
        $response['message'] = "Assignment submitted successfully";
    }else{
        $response['error'] = true;
        $response['message'] = "Could not submit assignment";
    }
    echoResponse(200,$response);
});


/* *
 * URL: http://localhost/StudentApp/v1/students
 * Parameters: none
 * Authorization: Put API Key in Request Header
 * Method: GET
 * */
$app->get('/students', 'authenticateFaculty', function() use ($app){
    $db = new DbOperation();
    $result = $db->getAllStudents();
    $response = array();
    $response['error'] = false;
    $response['students'] = array();

    while($row = $result->fetch_assoc()){
        $temp = array();
        $temp['id'] = $row['id'];
        $temp['name'] = $row['name'];
        $temp['username'] = $row['username'];
        array_push($response['students'],$temp);
    }

    echoResponse(200,$response);
});

function echoResponse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response);
}


function verifyRequiredParams($required_fields)
{
    $error = false;
    $error_fields = "";
    $request_params = $_REQUEST;

    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }

    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

function authenticateStudent(\Slim\Route $route)
{
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    if (isset($headers['Authorization'])) {
        $db = new DbOperation();
        $api_key = $headers['Authorization'];
        if (!$db->isValidStudent($api_key)) {
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoResponse(401, $response);
            $app->stop();
        }
    } else {
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoResponse(400, $response);
        $app->stop();
    }
}


function authenticateFaculty(\Slim\Route $route)
{
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
    if (isset($headers['Authorization'])) {
        $db = new DbOperation();
        $api_key = $headers['Authorization'];
        if (!$db->isValidFaculty($api_key)) {
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoResponse(401, $response);
            $app->stop();
        }
    } else {
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoResponse(400, $response);
        $app->stop();
    }
}

$app->run();