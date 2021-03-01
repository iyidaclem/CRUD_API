<?php 

require_once('DB.php');
require_once('../Model/Response.php');

try{
  $writeDB = DB::connectWriteDB();
  $readDB = DB::connectReadDB();
}catch(PDOException $ex){
  $response = new Response();
  $response->setHttpStatuseCode(500);
  $response->setSuccess(false);
  $response->addMessage("Database connection error");
  $response->send();
 

}