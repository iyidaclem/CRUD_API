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

if(array_key_exists("sessionid", $_GET)){

  $sessionID = $_GET['sessionid'];

  if($sessionID == '' || !is_numeric($sessionID)){
    $response = new Response();
    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    ($sessionID === ''? $response->addMessage("Session ID cannot be blank."):false);
    (!is_numeric($sessionID) ? $response->addMessage("Session ID must be numeric"): false);
    $response->send();
  }

  if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1){
    $response = new Response();
    $response->setHttpStatuseCode(401);
    $response->setSuccess(false);
    (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $response->addMessage("Access token is missing from the header.") : false);
    (strlen($_SERVER['HTTP_AUTHORIZATION']) < 1 ? $response->addMessage("Access token cannot be blank.") : false);
    $response->send();
    exit;
  }

  $accesstoken = $_SERVER['HTTP_AUTHORIZATION'];


  if($_SERVER['REQUEST_METHOD'] === 'DELETE'){

    try{
      $query = $writeDB->prepare('DELETE from table_sessions where id = :sessionid and accesstoken = :accesstoken');
      $query->bindParam(':sessionid', $sessionID, PDO::PARAM_INT);
      $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
      $query->execute();

      $rowCount = $query->rowCount();

      if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatuseCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to log out session using the access token provided.");
        $response->send();
      }

      $returnData = array();
      $returnData['session_id'] = intval($sessionID);

      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->addMessage("Successfully logged out.");
      $response->setData($returnData);
      $response->send();

    }catch(PDOException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("There was a problem loggin out, please try again.");
      $response->send();
    }

  }elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){

    //we are using PATCH here for refreshing. Not edit.

    if($_SERVER['CONTENT_TYPE']!== 'application/json'){
      $response = new Response();
      $response->setHttpStatuseCode(400);
      $response->setSuccess(false);
      $response->addMessage("Content header not set to json.");
      $response->send();
    }
    //Getting the json data passed 
    $rawPatchData = file_get_contents('php://input');

    if(!$jsonData = json_decode($rawPatchData)){
      $response = new Response();
      $response->setHttpStatuseCode(404);
      $response->setSuccess(false);
      $response->addMessage("Passed in data is not a valid json format");
      $response->send();
    }

    if(!isset($jsonData->refresh_token) || strlen($jsonData->refresh_token) <1){
      $response = new Response();
      $response->setHttpStatuseCode(400);
      $response->setSuccess(false);
      (!isset($jsonData->refresh_token)? $response->addMessage("Refresh token not provided."):false);
      (strlen($jsonData->refresh_token) <1 ? $response->addMessage("Refresh token cannot be empty"):false);    
      $response->send();  
    }

    //Now creating and executing database queries under try catch
    try{
      $refreshtoken = $jsonData->refresh_token;

      $query = $writeDB->prepare('SELECT table_sessions.id as sessionid, table_sessions.userid as userid, accesstoken, refreshtoken, useractive, loginattempts, accesstokenexpiry, refreshtokenexpiry from table_sessions, table_users where table_users.id = table_sessions.userid and table_sessions.id = :sessionid and table_sessions.accesstoken = :accesstoken and table_sessions.refreshtoken = :refreshtoken');
      $query->bindParam(':sessionid', $sessionID, PDO::PARAM_INT);
      $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
      $query->bindParam(':refreshtoken', $refreshtoken, PDO::PARAM_STR);
      $query->execute();
      $rowCount = $query->rowCount();

      if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("Access or Refresh token is incorrect for the session id");
        $response->send();  
      }

      $row = $query->fetch(PDO::FETCH_ASSOC);

      $returned_sessionid = $row['sessionid'];
      $returned_userid = $row['userid'];
      $returned_accesstoken = $row['accesstoken'];
      $returned_refreshtoken = $row['refreshtoken'];
      $returned_useractive = $row['useractive'];
      $returned_loginattempts = $row['loginattempts'];
      $returned_accesstokenexpiry = $row['accesstokenexpiry'];
      $returned_refreshtokenexpiry = $row['refreshtokenexpiry'];

      if($returned_useractive !== 'Y'){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("This user account is inactive.");
        $response->send();
      }

      if($returned_loginattempts >= 3){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("The user is currently locked out.");
        $response->send();
      }
      //Checking to see if the refresh token expiry time is a future time
      //or if it has expired. If it is less than the now, then it has 
      //expired.
      if(strtotime($returned_refreshtokenexpiry)<time()){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("Sorry the refresh token provided has expired.");
        $response->send();
      }

      $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
  
      // generate refresh token
      // use 24 random bytes to generate a refresh token then encode this as base64
      // suffix with unix time stamp to guarantee uniqueness (stale tokens)
      $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
  

      $access_token_expiry_seconds = 1200;
      $refresh_token_expiry_seconds = 1209600;

      // $query = $writeDB->prepare('update table_sessions set accesstoken = :accesstoken, 
      //       accesstokenexpiry = date_add(NOW(),INTERVAL :accesstokenexpiryseconds SECOND) refreshtoken =:refreshtoken, 
      //       refreshtokenexpiry = date_add(NOW(), INTERVAL :refreshtokenexpirysecond SECOND) where id =:sessionid and 
      //       userid =:userid and accesstoken =:returnedaccesstoken and refreshtoken =:returnedrefreshtoken');
      
      // $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
      // $query->bindParam(':sessionid', $returned_sessionid, PDO::PARAM_INT);
      // $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
      // $query->bindParam(':accesstokenexpirysecond', $access_token_expiry_seconds, PDO::PARAM_INT);
      // $query->bindParam(':refreshtoken', $refreshtoken, PDO::PARAM_STR);
      // $query->bindParam(':refreshtokenexpiry', $refresh_token_expiry_seconds, PDO::PARAM_STR);
      // $query->bindParam(':returnedaccesstoken', $returned_accesstoken, PDO::PARAM_STR);
      // $query->bindParam(':returnedrefreshtoken', $returned_refreshtoken, PDO::PARAM_STR);
      // $query->execute();

        // create the query string to update the current session row in the sessions table and set the token and refresh token as well as their expiry dates and times
        $query = $writeDB->prepare('update table_sessions set accesstoken = :accesstoken, accesstokenexpiry = 
        date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), refreshtoken = :refreshtoken, refreshtokenexpiry 
        = date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND) where id = :sessionid and userid = :userid and 
        accesstoken = :returnedaccesstoken and refreshtoken = :returnedrefreshtoken');
        // bind the user id
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        // bind the session id
        $query->bindParam(':sessionid', $returned_sessionid, PDO::PARAM_INT);
        // bind the access token
        $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
        // bind the access token expiry date
        $query->bindParam(':accesstokenexpiryseconds', $access_token_expiry_seconds, PDO::PARAM_INT);
        // bind the refresh token
        $query->bindParam(':refreshtoken', $refreshtoken, PDO::PARAM_STR);
        // bind the refresh token expiry date
        $query->bindParam(':refreshtokenexpiryseconds', $refresh_token_expiry_seconds, PDO::PARAM_INT);
        // bind the old access token for where clause as user could have multiple sessions
        $query->bindParam(':returnedaccesstoken', $returned_accesstoken, PDO::PARAM_STR);
        // bind the old refresh token for where clause as user could have multiple sessions
        $query->bindParam(':returnedrefreshtoken', $returned_refreshtoken, PDO::PARAM_STR);
        // run the query
        $query->execute();

      $rowCount = $query->rowCount();

      if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatuseCode(401);
        $response->setSuccess(false);
        $response->addMessage("Access token could not be refreshed- please log in again.");
        $response->send();
        exit();
      }

      $returnData = array();
      $returnData['sessionid'] = $returned_sessionid;
      $returnData['access_token'] = $accesstoken;
      $returnData['access_token_expiry'] = $access_token_expiry_seconds;
      $returnData['refresh_token'] = $refreshtoken;
      $returnData['refresh_token_expiry'] = $refresh_token_expiry_seconds;
      
      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->setData($returnData);
      $response->addMessage("Token refreshed.");
      $response->send();
      exit();




    }catch(PDOException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(401);
      $response->setSuccess(false);
      $response->addMessage("Server or query error ".$ex);
      $response->send();
      exit();
    }



  }else{
    $response = new Response();
    $response->setHttpStatuseCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint not found.");
    $response->send();
  }


 
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
  $response->setSuccess(false);
  $response->addMessage("You are trying to access a non-existing endpoint.");
  $response->send();
}
