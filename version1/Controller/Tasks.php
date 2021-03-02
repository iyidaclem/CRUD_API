<?php 

require_once('DB.php');
require_once('../Model/Tasks.php');
require_once('../Model/Response.php');
//Establishing database
try{
  $writeDB = DB::connectWriteDB();
  $readDB = DB::connectReadDB();
}catch(PDOException $ex){ 
  //Sending error message and the correct code
  error_log("Connection error - ".$ex, 0);
  $response = new Response();
  $response->setHttpStatuseCode(500);
  $response->setSuccess(false);
  $response->addMessage("Database connect error");
  $response->send();
}
//Making sure that a taskID is included in an incoming request
if(array_key_exists("taskid", $_GET)){
  $taskid = $_GET['taskid'];

  //making sure that taskID isnt empty and it is numeric charachater
  if($taskid == ' ' || !is_numeric($taskid)){
    $response = new Response();

    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    $response->addMessage("Task Id cannot be blank and must be numerica.");
    $response->send();
    exit;
  }

  if($_SERVER['REQUEST_METHOD'] === 'GET'){
    try{
      $query = $readDB->prepare('select id, title, descript, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from table_tasks where id=:taskid');
      //Preparing and executing query
      $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
      $query->execute(); 
      
      //To check if the query found something on 
      //any of the rows in our database

      $rowCount = $query->rowCount();

      if($rowCount ===0){
        $response = new Response();
        $response->setHttpStatuseCode(404);
        $response->setSuccess(false);
        $response->addMessage("Task not found");
        $response->send();
        exit;
      }
      // while($row = $query->fetch(PDO::FETCH_ASSOC)){
      //   $task = new Task($row['id'], $row['title'],$row['description'], $row['deadline'], $row['completed']);
      //   $taskArray[] = $task->returnTaskArray();
      // }
      $row = $query->fetch(PDO::FETCH_ASSOC);
      $task = new Task($row['id'], $row['title'],$row['descript'], $row['deadline'], $row['completed']);
      $taskArray[] = $task->returnTaskArray();

      $returnData = array();
      $returnData['rows_returned'] = $rowCount;
      $returnData['tasks'] = $taskArray;

      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->toCache(true);
      $response->setData($returnData);
      $response->send();
      exit();

    }
    catch(TaskException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage($ex->getMessage());
      $response->send();
      exit();
    }
    catch(PDOException $ex){
      error_log("Database query error - ".$ex, 0);
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to get task");
      $response->send();
      exit();
    }


  }elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
    try{

      $query = $writeDB->prepare('DELETE FROM table_tasks WHERE id=:taskid');
      $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
      $query->execute();

      $rowCount = $query->rowCount();

      if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatuseCode(404);
        $response->setSuccess(false);
        $response->addMessage("Task not found ".$ex->getMessage());
        $response->send();
        exit();
      }

      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->addMessage("Task deleted.");
      $response->send();
      exit();

    }catch(PDOException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess("false");
      $response->addMessage("Failed to delete task");
      $response->send();
      exit();
    }
  }elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){
 
  }else{
    $response = new Response();
    $response->setHttpStatuseCode(405);
    $response->setSuccess(false);
    $response->addMessage("Request method not allowed");
    $response->send();
  }
}elseif(array_key_exists("completed", $_GET)){
  
  $completed = $_GET['completed'];

  if($completed !== 'Y' && $completed !== 'N'){
    $response = new Response();
    $response->setHttpStatuseCode(400);
    $response->setSuccess(false);
    $response->addMessage("Completed filter must be Y or N");
    $response->send();
    exit();
  }
  if($_SERVER['REQUEST_METHOD'] === 'GET'){

    try{
      $query = $readDB->prepare('SELECT id, title, descript, DATE_FORMAT(deadline,"%d/%m/%Y %H:%i") AS deadline, completed FROM table_tasks WHERE completed=:completed');  
      $query->bindParam(':completed', $completed, PDO::PARAM_STR);
      $query->execute();

      $rowCount = $query->rowCount();

      $taskArray = array();
      while($row = $query->fetch(PDO::FETCH_ASSOC)){
        $task = new Task($row['id'], $row['title'], $row['descript'], $row['deadline'], $row['completed']);
        $taskArray[] = $task->returnTaskArray();
      }
      $returnData = array();
      $returnData['rows_returned'] = $rowCount;
      $returnData['tasks'] = $taskArray;

      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->toCache(true);
      $response->setData($returnData);
      $response->send();
      exit();

    
    }catch(TaskException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage($ex->getMessage());
      $response->send();
    }
    catch(PDOException $ex){
      error_log("Database query error - ".$ex);
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to get tasks.");
      $response->send();
    }

  }else{
    $response = new Response();
    $response->setHttpStatuseCode(405);
    $response->setSuccess(false);
    $response->addMessage("Request method not allowed");
    $response->send();
  }
}elseif(array_key_exists("page", $_GET)){
  if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $page = $_GET['page'];

    if($page == '' || !is_numeric($page)){
      $response = new Response();
      $response->setHttpStatuseCode(400);
      $response->setSuccess(false);
      $response->addMessage("Page number is either blank or non-numeric.");
      $response->send();
      exit();
    }

    $limitPerPage = 20;

    try{
      $query = $readDB->prepare('select count(id) as totalNoOfTasks from table_tasks');
      $query->execute();
      $row = $query->fetch(PDO::FETCH_ASSOC);

      $tasksCount = intval($row['totalNoOfTasks']);
      
      $numOfPages = ceil($tasksCount/$limitPerPage);
      
      if($numOfPages == 0){
        $numOfPages =1;
      }
      if($page >$numOfPages){
        $response = new Response();
        $response->setHttpStatuseCode(404);
        $response->setSuccess(false);
        $response->addMessage("Page not found.");
        $response->send();
        exit();
      }
      $offSet = ($page == 1? 0: ($limitPerPage*($page-1)));
      $query = $readDB->prepare('SELECT id, title, descript, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") AS deadline, completed FROM table_tasks LIMIT :pglimit OFFSET :offset');
      $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
      $query->bindParam(':offset', $offSet, PDO::PARAM_INT);
      $query->execute();

      $rowCount = $query->rowCount();
      $taskArray = array();

      while($row = $query->fetch(PDO::FETCH_ASSOC)){
        $task = new Task($row['id'],$row['title'], $row['descript'], $row['deadline'], $row['completed']);
        $taskArray[] = $task->returnTaskArray();
      }
      $returnData = array();
      $returnData['rows_returned'] = $rowCount;
      $returnData['total_rows'] = $tasksCount;
      $returnData['total_pages'] = $numOfPages;
      ($page < $numOfPages ? $returnData['has_next_page']= true : $returnData['has_next_page'] = false);
      ($page > 1 ? $returnData['has_previous_page']= true : $returnData['has_previous_page'] = false);
      $returnData['tasks'] = $taskArray;

      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->toCache(true);
      $response->setData($returnData);
      $response->send();
      exit();

    }catch(TaskException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage($ex->getMessage());
      $response->send();
      exit();
    }catch(PDOException $ex){
      error_log("Database query error -".$ex);
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to get tasks.".$ex->getMessage());
      $response->send();
      exit();
    }

  }else{
    $response = new Response();
    $response->setHttpStatuseCode(405);
    $response->setSuccess(false);
    $response->addMessage("Request method not allowed");
    $response->send();
  }
}
elseif(empty($_GET)){ 
  if($_SERVER['REQUEST_METHOD'] === 'GET'){

    try{
      $query = $readDB->prepare('select id, title, descript, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from table_tasks');
      $query->execute();

      $rowCount = $query->rowCount();
      $taskArray = array();

      while($row = $query->fetch(PDO::FETCH_ASSOC)){
        $task = new Task($row['id'], $row['title'], $row['descript'], $row['deadline'], $row['completed']);
        $taskArray[] = $task->returnTaskArray();
      }

      $returnData = array();
      $returnData['returned'] = $rowCount;
      $returnData['tasks'] = $taskArray;

      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->toCache(true);
      //$response($returnData);
      $response->setData($returnData);
      //$response->addMessage("All Tasks successfully fetched.");
      $response->send();
      exit();


    }catch(TaskException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage($ex->getMessage());
      $response->send();
      exit();

    }catch(PDOException $ex){
      error_log("Database query erro -".$ex, 0);
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to retrieve tasks" . $er);
      $response->send();
      exit();

    }

  }elseif($_SERVER['REQUEST_METHOD']== 'POST'){

    try{

    }
    catch(TaskException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(400);
      $response->setSuccess(false);
      $response->addMessage($ex->getMessage());
      $response->send();
      exit();
    }
    catch(PDOException $ex){
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to post to database ".$ex->getMessage());
      $response->send();
    }


  }else{
    $response = new Response();
    $response->setHttpStatuseCode(405);
    $response->setSuccess(false);
    $response->addMessage("Request method not allowed");
    $request->send();
    exit();
  }
}else{
  $response = new Response();
  $response->setHttpStatuseCode(404);
  $response->setSuccess(false);
  $response->addMessage("Endpoint not found");
  $response->send();
}

// /V1/tasks/complete
// v1/tasks/incomplete

//v1/tasks.php?completed
