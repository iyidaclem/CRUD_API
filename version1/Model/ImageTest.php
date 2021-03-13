<?php

require_once('ImageModel.php');

try{
  $image = new Image(1,"Image title here", "image1.docx", "image/jpg", 3, "");
  header('Content-type: application/json:charset=UTF-9');
  echo json_encode($image->returnImageAsArray());
}catch(ImageException $ex){
  echo "error:".$ex->getMessage();
}