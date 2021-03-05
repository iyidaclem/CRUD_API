<?php 

require_once('DB.php');
require_once('../Model/Response.php');

try{

}catch(PDOException $ex){
  error_log("Connection error ".$x, 0);
  $response = new Response();
  $response->setHttpStatuseCode(500);
  $respnse->setSuccess(false);
  $response->addMessage("Database connection error".$ex->getMessage());
  $response->send();
}

if(array_key_exists("sesionid", $_GET)){

}elseif(empty($_GET)){

  if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    $response = new Response();
    $response->setHttpStatuseCode(405);
    $respnse->setSuccess(false);
    $response->addMessage("Wrong HTTP request method. Only POST is allowed in this endpoint.");
    $response->send();
    exit();
  }
  sleep(1);

  if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
    $response = new Response();
    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    $response->addMessage("Request content type header is not application/json");
    $response->send();
    exit();
  }

  $rawPostData = file_get_contents('php://input');
  if(!$jsonData = json_decode($rawPostData)){
    $response = new Response();
    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    $response->addMessage("Request body is not a valid json.");
    $response->send();
    exit();
  }

  if(!isset($jsonData->username) || (!isset($jsonData->password))){
    $response = new Response();
    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    (!isset($jsonData->username))? $response->addMessage("Username not supplied."): false;
    (!isset($jsonData->password))? $response->addMessage("Password not supplied."): false;
    $response->send();
    exit();
  }

  if(strlen($jsonData->username)<1 || 
    strlen($jsonData->username)>255 ||
    strlen($jsonData->password)<6 ||
    strlen($jsonData->password)>255){

      $response = new Response();
      $response->setHttpStatuseCode(400);
      $response->setSuccess(false);
      strlen($jsonData->username) <1 ? $response->addMessage("Username must be at least six characters"): false;
      strlen($jsonData->username) >255 ? $response->addMessage("Too many characters supplied for username"): false;
      strlen($jsonData->password) <1 ? $response->addMessage("Password must be at least six characters"): false;
      strlen($jsonData->password) >255 ? $response->addMessage("Too many characters supplied for password."): false;
      $response->send();
      exit();
    }

    try{

      $username = $jsonData->username;
      $password = $jsonData->password;

      $query = $writeDB->prepare('select id, fullname, username, password, useractive, loginattempts from table_users where username = :username');
      $query->bindParam(':username', $username, PDO::FETCH_ASSOC);
      $query->execute();

      $rowCount = $query->rowCount();

      if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("Username or passeord incorrect");
        exit();
      }

      $row = $query->fetch(PDO::FETCH_ASSOC);

      $returned_id = $row['id'];
      $returned_fullname = $row['fullname'];
      $returned_username = $row['username'];
      $returned_password = $row['password'];
      $returned_useractive = $row['useractive'];
      $returned_loginattempts = $row['loginattempts'];

      if($returned_useractive !== 'Y'){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("user account not active");
        $response->send();
        exit();
      }

      if($returned_loginattempts >= 3){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("User account is currently active.");
        $response->send();
        exit();
      }

      if(!password_verify($password, $returned_password)){
        $query = $writeDB->prepare('update table_users set loginattempts = loginattempts+1 where id =:id');
        $query->bindParam(':id', $returned_id, PDO::FETCH_ASSOC);
        $query->execute();

        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("Username or password incorrect.");
        $response->send();
        exit();
      }

      $accessToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
      $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

      $access_token_expiry_seconds = 1200;
      $refresh_token_expiry_seconds = 1209600;

    }catch(PDOException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to login.");
      exit();
    }

    try{
        $writeD->beginTransaction();
        
        $query = $writeDB->prepare('update set loginattempts = 0 where id =:id');
        $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
        $query->execute();

        $query = $writeDB->prepare('insert table_sessions ')

    }catch(PDOException $ex){
      $writeDB->rollback();
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to login.");
      exit();
    }

  // $id = $jsonData->id;
  // $userid = $jsonData->userid;
  // $accessToken = $jsonData->accessToken;
  // $keyExpirey = $jsonData->accesstokenexpiry;
  // $refreshtoken = $jsonData->refreshtoken;
  // $refreshtokenExpiry = $jsonData->refreshtokenexpiry;


}else{
  $response = new Response();
  $response->setHttpStatuseCode(404);
  $respnse->setSuccess(false);
  $response->addMessage("You are trying to access a non-existing endpoint.");
  $response->send();
}
