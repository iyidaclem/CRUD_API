<?php

require_once('Tasks.php');

try{
  $task = new Task(1, "Title Here", "Description Here", "01/01/2020 12:00", "N");
  header('Content-type:application/json;charset=UTF-8');
  echo json_encode($task->returnTaskArray());
}catch(TaskException $ex){
  echo "Error: ".$ex->getMessage();
}