<?php 

require_once('DB.php');
require_once('../Model/Response.php');

try{
  $writeDB = DB::connectWriteDB();
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
    $response->setSuccess(false);
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

      // $username = $jsonData->username;
      // $password = $jsonData->password;

      // $query = $writeDB->prepare('select id, fullname, username, password, useractive, loginattempts from table_users where username = :username');
      // $query->bindParam(':username', $username, PDO::FETCH_ASSOC);
      // $query->execute();

      // $rowCount = $query->rowCount();

      // if($rowCount === 0){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(401);
      //   $response->setSuccess(false);
      //   $response->addMessage("Username or passeord incorrect");
      //   exit();
      // }

      // $row = $query->fetch(PDO::FETCH_ASSOC);

      // $returned_id = $row['id'];
      // $returned_fullname = $row['fullname'];
      // $returned_username = $row['username'];
      // $returned_password = $row['password'];
      // $returned_useractive = $row['useractive'];
      // $returned_loginattempts = $row['loginattempts'];

      // if($returned_useractive !== 'Y'){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(401);
      //   $response->setSuccess(false);
      //   $response->addMessage("user account not active");
      //   $response->send();
      //   exit();
      // }

      // if($returned_loginattempts >= 3){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(401);
      //   $response->setSuccess(false);
      //   $response->addMessage("User account is currently active.");
      //   $response->send();
      //   exit();
      // }

      // if(!password_verify($password, $returned_password)){
      //   $query = $writeDB->prepare('update table_users set loginattempts = loginattempts+1 where id =:id');
      //   $query->bindParam(':id', $returned_id, PDO::FETCH_ASSOC);
      //   $query->execute();

      //   $response = new Response();
      //   $response->setHttpStatuseCode(401);
      //   $response->setSuccess(false);
      //   $response->addMessage("Username or password incorrect.");
      //   $response->send();
      //   exit();
      // }

      // $accessToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
      // $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

      // $access_token_expiry_seconds = 1200;
      // $refresh_token_expiry_seconds = 1209600;

      $username = $jsonData->username;
      $password = $jsonData->password;
      // create db query
      $query = $writeDB->prepare('SELECT id, fullname, username, password, useractive, loginattempts from table_users where username = :username');
      $query->bindParam(':username', $username, PDO::PARAM_STR);
      $query->execute();
  
      // get row count
      $rowCount = $query->rowCount();
  
      if($rowCount === 0) {
        // set up response for unsuccessful login attempt - obscure what is incorrect by saying username or password is wrong
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("Username or password is incorrect");
        $response->send();
        exit;
      }
  
      // get first row returned
      $row = $query->fetch(PDO::FETCH_ASSOC);
  
      // save returned details into variables
      $returned_id = $row['id'];
      $returned_fullname = $row['fullname'];
      $returned_username = $row['username'];
      $returned_password = $row['password'];
      $returned_useractive = $row['useractive'];
      $returned_loginattempts = $row['loginattempts'];
  
      // check if account is active
      if($returned_useractive != 'Y') {
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("User account is not active");
        $response->send();
        exit;
      }
  
      // check if account is locked out
      if($returned_loginattempts >= 3) {
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("User account is currently locked out");
        $response->send();
        exit;
      }
  
      // check if password is the same using the hash
      if(!password_verify($password, $returned_password)) {
        // create the query to increment attempts figure
        $query = $writeDB->prepare('update table_users set loginattempts = loginattempts+1 where id = :id');
        // bind the user id
        $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
        // run the query
        $query->execute();
  
        // send response
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("Username or password is incorrect");
        $response->send();
        exit;
      }
  
      // generate access token
      // use 24 random bytes to generate a token then encode this as base64
      // suffix with unix time stamp to guarantee uniqueness (stale tokens)
      $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
  
      // generate refresh token
      // use 24 random bytes to generate a refresh token then encode this as base64
      // suffix with unix time stamp to guarantee uniqueness (stale tokens)
      $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
  
      // set access token and refresh token expiry in seconds (access token 20 minute lifetime and refresh token 14 days lifetime)
      // send seconds rather than date/time as this is not affected by timezones
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
      $writeDB->beginTransaction();
      // create the query string to reset attempts figure after successful login
      $query = $writeDB->prepare('update table_users set loginattempts = 0 where id = :id');
      // bind the user id
      $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
      // run the query
      $query->execute();
  
      // create the query string to insert new session into sessions table and set the token and refresh token as well as their expiry dates and times
      $query = $writeDB->prepare('insert into table_sessions (userid, accesstoken, accesstokenexpiry, refreshtoken, refreshtokenexpiry) 
      values (:userid, :accesstoken, date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND))');
      // bind the user id
      $query->bindParam(':userid', $returned_id, PDO::PARAM_INT);
      // bind the access token
      $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
      // bind the access token expiry date
      $query->bindParam(':accesstokenexpiryseconds', $access_token_expiry_seconds, PDO::PARAM_INT);
      // bind the refresh token
      $query->bindParam(':refreshtoken', $refreshtoken, PDO::PARAM_STR);
      // bind the refresh token expiry date
      $query->bindParam(':refreshtokenexpiryseconds', $refresh_token_expiry_seconds, PDO::PARAM_INT);
      // run the query
      $query->execute();
  
      // get last session id so we can return the session id in the json
      $lastSessionID = $writeDB->lastInsertId();
  
      // commit new row and updates if successful
      $writeDB->commit();
  
      // build response data array which contains the access token and refresh tokens
      $returnData = array();
      $returnData['session_id'] = intval($lastSessionID);
      $returnData['access_token'] = $accesstoken;
      $returnData['access_token_expires_in'] = $access_token_expiry_seconds;
      $returnData['refresh_token'] = $refreshtoken;
      $returnData['refresh_token_expires_in'] = $refresh_token_expiry_seconds;
  
      $response = new Response();
      $response->setHttpStatuseCode(201);
      $response->setSuccess(true);
      $response->setData($returnData);
      $response->send();
      exit;

    }catch(PDOException $ex){
      $writeDB->rollback();
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to login.");
      exit();
    }

}else{
  $response = new Response();
  $response->setHttpStatuseCode(404);
  $respnse->setSuccess(false);
  $response->addMessage("You are trying to access a non-existing endpoint.");
  $response->send();
}
