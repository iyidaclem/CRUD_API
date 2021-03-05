<?php
//Importing the files needed or dependencies
require_once('DB.php');
require_once('../Model/Response.php');


try{

  $writeDB = DB::connectWriteDB();

}catch(PDOException $ex){
  error_log("Connection Error: ".$ex, 0);
  $response = new Response();
  $response->setHttpStatuseCode(500);
  $response->setSuccess(false);
  $response->addMessage("database connection error ".$ex->getMessage());
  $response->send();
  exit();
}
//Confirming that request method is POST and bouncing off any other request
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  $response = new Response();
  $response->setHttpStatuseCode(405);
  $response->setSuccess(false);
  $response->addMessage("Request method not allowed.");
  $response->send();
  exit();
}

//Verifying that request content header type is application/json
if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
  $response = new Response();
  $response->setHttpStatuseCode(405);
  $response->setSuccess(false);
  $response->addMessage("Content header not json");
  $response->send();
  exit();
}

$rawPostData = file_get_contents('php://input');

if(!$jsonData =json_decode($rawPostData)){
  $response = new Response();
  $response->setHttpStatuseCode(405);
  $response->setSuccess(false);
  $response->addMessage("Request not a valid JSON format.");
  $response->send();
  exit();
}

if(!isset($jsonData->fullname) || !isset($jsonData->username) || !isset($jsonData->password)){
  $response = new Response();
  $response->setHttpStatuseCode(400);
  $response->setSuccess(false);
  (!isset($jsonData->fullname) ? $response->addMessage("FullName not supplied"):false);
  (!isset($jsonData->username) ? $response->addMessage("username not supplied"):false);
  (!isset($jsonData->password) ? $response->addMessage("password not supplied"):false);
  $response->send();
  exit();
}

if(strlen($jsonData->fullname)<3 || strlen($jsonData->fullname)>255 || 
  strlen($jsonData->username)<6 || strlen($jsonData->username)>255 || 
  strlen($jsonData->password)<6 || strlen($jsonData->password)>255)
{
  $response = new Response();
  $response->setHttpStatuseCode(400);
  $response->setSuccess(false);
  $jsonData->fullname<3 ? $response->addMessage("Fullname is too short"):false;
  $jsonData->fullname>255 ? $response->addMessage("Fullname is too too long"):false;
  $jsonData->username < 6 ? $response->addMessage("username not supplied"):false;
  $jsonData->username > 255 ? $response->addMessage("Username cannot exceed 12 characters"):false;
  $jsonData->password < 6? $response->addMessage("password not supplied"):false;
  $jsonData->password > 255? $response->addMessage("password cannot exceed 12 characters"):false;
  $response->send();
  exit(); 
}

$fullname = trim($jsonData->fullname);
$username = trim($jsonData->username);
$password = $jsonData->password;

try{
  $query = $writeDB->prepare('SELECT id from table_users where username=:username');
  $query->bindParam(':username', $username, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();

  if($rowCount !== 0){
    $response = new Response();
    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    $response->addMessage("Username already exits in the database.");
    $response->send();
    exit();
  }

  $hash_password = password_hash($jsonData->password, PASSWORD_DEFAULT);
  
  $query = $writeDB->prepare('INSERT into table_users (fullname, username, password) values (:fullname, :username, :password)');
  $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
  $query->bindParam(':username', $username, PDO::PARAM_STR);
  $query->bindParam(':password', $password, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();

  if($rowCount === 0){
    $response = new Response();
    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    $response->addMessage("Account creation have failed.");
    $response->send();
    exit();
  }
  $lastUserId = $writeDB->lastInsertId();

  $returnData = array();
  $returnData['user_id'] = $lastUserId;
  $returnData['fullname'] = $jsonData->fullname;
  $returnData['username'] = $jsonData->username;

  $response = new Response();
  $response->setHttpStatuseCode(201);
  $response->setSuccess(true);
  $response->addMessage("User created");
  $response->send();
  exit();


}catch(PDOException $ex){
  error_log("Database query error: ".$ex, 0);
  $response = new Response();
  $response->setHttpStatuseCode(500);
  $response->setSuccess(false);
  $response->addMessage("There was an issue creating a user account. Please try again".$ex->getMessage());
  $response->send();
  exit();
}