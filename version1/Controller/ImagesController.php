<?php 

require_once('DB.php');
require_once('../Model/Response.php');
require_once('../Model/ImageModel.php');

function sendResponse($setStatusCode, $success, $message= null, $toCach = false, $data = null){
  $response = new Response();
  $response->setHttpStatuseCode($setStatusCode); 
  $response->setSuccess($success);
  if($message != null){
    $response->addMessage($message);
  }
  if($data != null){
    $response->setData($data);
  }
  $response->toCache($toCach);
  $response->send();
  exit();
}

// function to check authorisation status
function checkAuthStatusAndReturnUserID($writeDB) {
  // BEGIN OF AUTH SCRIPT
  // Authenticate user with access token
  // check to see if access token is provided in the HTTP Authorization header and that the value is longer than 0 chars
  // don't forget the Apache fix in .htaccess file
  if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
   $message = null;
    if(!isset($_SERVER['HTTP_AUTHORIZATION'])) {
      $message = "Access token is missing from the header";
    } else {
      if(strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
          $message = "Access token cannot be blank";
      }
    }
    sendResponse(401, false, $message);
  }

  // get supplied access token from authorisation header - used for delete (log out) and patch (refresh)
  $accesstoken = $_SERVER['HTTP_AUTHORIZATION'];

  // attempt to query the database to check token details - use write connection as it needs to be synchronous for token
  try {
    $query = $writeDB->prepare('select userid, accesstokenexpiry, useractive, loginattempts from table_sessions, table_users where table_sessions.userid = table_users.id and accesstoken = :accesstoken');
    $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
    $query->execute();

    $rowCount = $query->rowCount();

    if($rowCount === 0) {
      sendResponse(401, false, "Invalid access token");
    }

    $row = $query->fetch(PDO::FETCH_ASSOC);
    // save returned details into variables
    $returned_userid = $row['userid'];
    $returned_accesstokenexpiry = $row['accesstokenexpiry'];
    $returned_useractive = $row['useractive'];
    $returned_loginattempts = $row['loginattempts'];

    if($returned_useractive != 'Y') {
      sendResponse(401, false, "User account is not active");
    }
    if($returned_loginattempts >= 3) {
      sendResponse(401, false, "User account is currently locked out");
    }

    if(strtotime($returned_accesstokenexpiry) < time()) {
      sendResponse(401, false, "Access token has expired");
    }
    return $returned_userid;
  }
  catch(PDOException $ex){
    sendResponse(500, false, "There was an issue authenticating - please try again");
  }

}

try{
  $writeDB = DB::connectWriteDB();
  $readDB = DB::connectReadDB();
}catch(PDOException $ex){
  sendResponse(500,false, "Database connection error.".$ex->getMessage()); 
} 
if(array_key_exists("taskid", $_GET) && array_key_exists("imageid", $_GET) && array_key_exists("attribute", $_GET)){
  $taskid = $_GET['taskid'];
  $imageid = $_GET['imageid'];
  $attributes = $_GET['attributes'];

  if($imageid == '' || !is_numeric($imageid) || !is_numeric($taskid)){
    sendResponse(400, false, "Image ID or Task ID ");
  }
  if($_SERVER['REQUEST_METHOD'] ==='GET'){

  }elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){

  }else{
    sendResponse(500, false, "Image ID or task ID must not be blank");
  }
}
elseif(array_key_exists("taskid", $_GET) && array_key_exists("imageid", $_GET)){
  $taskid = $_GET['taskid'];
  $imageid = $_GET['imageid'];

  if($imageid == '' || !is_numeric($imageid) || !is_numeric($taskid)){
    sendResponse(400, false, "Image ID or Task ID ");
  }

  if($_SERVER['REQUEST_METHOD'] === 'GET'){

  }elseif($_SERVER['REQUEST_METHOD']){

  }else{
    sendResponse(400, false, "Request method not allowed,");
  }
}elseif(array_key_exists("taskid", $_GET) && !array_key_exists("imageid", $_GET)){
  $taskid = $_GET["taskid"];

  if($taskid == '' || !is_numeric($taskid)){
    sendResponse(400, false, "Task ID cannot be blank.");
  }
  if($_SERVER['REQUEST_METHOD'] =='POST'){

  }else{
    sendResponse(400, false, "Request method not allowed");
  }
}else{
  sendResponse(400, false, "Endpoint not found.");  
}

