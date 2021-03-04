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
    // try{

    //    if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(400);
      //   $response->setSuccess(false);
      //   $response->addMessage("Content type header not set to Json.");
      //   $response->send();
      //   exit();
      // }
      // $rawPatchData = file_get_contents('php://input');

      // if(!$jsonData = json_decode($rawPatchData)){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(400);
      //   $response->setSuccess(false);
      //   $response->addMessage("Request body not a valid json");
      //   $response->send();
      //   exit();
      // }

      // $title_updated = false;
      // $description_updated = false;
      // $deadline_updated = false;
      // $completed_updated = false;

      // $patch_queryFields = "";

      // if(isset($jsonData->title)){
      //   $title_updated = true;
      //   $patch_queryFields .= "title = :title, ";
      // }
      // if(isset($jsonData->description)){
      //   $description_updated = true;
      //   $patch_queryFields .= "descript = :descript, ";
      // }
      // if(isset($jsonData->deadline)){
      //   $title_updated = true;
      //   $patch_queryFields .= "deadline = STR_TO_DATE(:deadline, '%d/%m/%Y %H:%i'),";
      // }
      // if(isset($jsonData->completed)){
      //   $completed_updated = true;
      //   $patch_queryFields .= "completed = :completed, ";
      // }

      // $patch_queryFields = rtrim($patch_queryFields, ", ");

      // if( $title_updated === false && $description_updated === false && $deadline_updated === false && $completed_updated === false){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(400);
      //   $response->setSuccess(false);
      //   $response->addMessage("No tasks fields provided.");
      //   $response->send();
      //   exit();
      // }

      // $query = $writeDB->prepare('SELECT id, title, descript, DATE_FORMAT(:deadline, "%d/%m/%Y %H:%i") as deadline, completed from table_tasks where id =:taskid');
      // $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
      // $query->execute();

      // $rowCount = $query->rowCount();

      // if($rowCount === 0){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(400);
      //   $response->setSuccess(false);
      //   $response->addMessage("Failed to update ".$ex->getMessage());
      //   $response->send();
      //   exit();
      // }

      // while($row = $query->fetch(PDO::FETCH_ASSOC)){
      //   $task = new Task($row['id'],$row['title'],$row['descript'], $row['deadline'], $row['completed']);
      // }
      // $queryString = "UPDATE table_tasks set ".$patch_queryFields." where id=:taskid";
      // $query = $writeDB->prepare($queryString);

      // if($title_updated === true){
      //   $task->setTitle($jsonData->title);
      //   $up_title = $task->getTitle();
      //   $query->bindParam(':title', $up_title, PDO::PARAM_STR);
      // }
      // if($description_updated === true){
      //   $task->setDescription($jsonData->description);
      //   $up_description = $task->getDescription();
      //   $query->bindParam(':descript', $up_title, PDO::PARAM_STR);
      // }
      // if($deadline_updated === true){
      //   $task->setDeadline($jsonData->deadline);
      //   $up_deadline = $task->getDeadline();
      //   $query->bindParam(':deadline', $up_deadline, PDO::PARAM_STR);
      // }
      // if($completed_updated === true){
      //   $task->setCompleted($jsonData->completed);
      //   $up_completed = $task->getComplete();
      //   $query->bindParam(':completed', $up_completed, PDO::PARAM_STR);
      // }

      // $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
      // $query->execute();

      // $rowCount = $query->rowCount();
      
      // if($rowCount === 0){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(400);
      //   $response->setSuccess(false);
      //   $response->addMessage("Task not update");
      //   $response->send();
      //   exit();
      // }
      // $query = $writeDB->prepare('SELECT id, title, descript, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from table_tasks where id =:taskid');
      // $query->bindParam(':taskid',$taskid, PDO::PARAM_INT);
      // $query->execute();

      // $rowCount = $query->rowCount();

      // if($rowCount === 0){
      //   $response = new Response();
      //   $response->setHttpStatuseCode(404);
      //   $response->setSuccess(false);
      //   $response->addMessage("Updated task not found.");
      //   $response->send();
      //   exit();
      // }

      // $taskArray = array();
      // while($row = $query->fetch(PDO::FETCH_ASSOC)){
      //   $task = new Task($row['id'], $row['title'], $row['descript'], $row['deadline'], $row['completed']);
      //   $taskArray = $task->returnTaskArray();
      // }

      // $returnData = array();
      // $returnData['rows_returned'] = $rowCount;
      // $returnData['tasks'] = $taskArray;
      
      // $response = new Response();
      // $response->setHttpStatuseCode(200);
      // $response->setSuccess(true);
      // $response->addMessage("Task updated");
      // $response->setData($returnData);
      // $response->send();
      // $exit();

       // update task
    try {
      // check request's content type header is JSON
      if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        // set up response for unsuccessful request
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        $response->addMessage("Content Type header not set to JSON");
        $response->send();
        exit;
      }

      // get PATCH request body as the PATCHed data will be JSON format
      $rawPatchData = file_get_contents('php://input');

      if(!$jsonData = json_decode($rawPatchData)) {
        // set up response for unsuccessful request
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        $response->addMessage("Request body is not valid JSON");
        $response->send();
        exit;
      }

      // set task field updated to false initially
      $title_updated = false;
      $description_updated = false;
      $deadline_updated = false;
      $completed_updated = false;

      // create blank query fields string to append each field to
      $queryFields = "";

      // check if title exists in PATCH
      if(isset($jsonData->title)) {
        // set title field updated to true
        $title_updated = true;
        // add title field to query field string
        $queryFields .= "title = :title, ";
      }

      // check if description exists in PATCH
      if(isset($jsonData->description)) {
        // set description field updated to true
        $description_updated = true;
        // add description field to query field string
        $queryFields .= "descript = :description, ";
      }

      // check if deadline exists in PATCH
      if(isset($jsonData->deadline)) {
        // set deadline field updated to true
        $deadline_updated = true;
        // add deadline field to query field string
        $queryFields .= "deadline = STR_TO_DATE(:deadline, '%d/%m/%Y %H:%i'), ";
      }

      // check if completed exists in PATCH
      if(isset($jsonData->completed)) {
        // set completed field updated to true
        $completed_updated = true;
        // add completed field to query field string
        $queryFields .= "completed = :completed, ";
      }

      // remove the right hand comma and trailing space
      $queryFields = rtrim($queryFields, ", ");

      // check if any task fields supplied in JSON
      if($title_updated === false && $description_updated === false && $deadline_updated === false && $completed_updated === false) {
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        $response->addMessage("No task fields provided");
        $response->send();
        exit;
      }
      // ADD AUTH TO QUERY
      // create db query to get task from database to update - use master db
      $query = $writeDB->prepare('SELECT id, title, descript, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from table_tasks where id = :taskid');
      $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
      $query->execute();

      // get row count
      $rowCount = $query->rowCount();

      // make sure that the task exists for a given task id
      if($rowCount === 0) {
        // set up response for unsuccessful return
        $response = new Response();
        $response->setHttpStatuseCode(404);
        $response->setSuccess(false);
        $response->addMessage("No task found to update");
        $response->send();
        exit;
      }

      // for each row returned - should be just one
      while($row = $query->fetch(PDO::FETCH_ASSOC)) {
        // create new task object
        $task = new Task($row['id'], $row['title'], $row['descript'], $row['deadline'], $row['completed']);
      }
      // ADD AUTH TO QUERY
      // create the query string including any query fields
      $queryString = "update table_tasks set ".$queryFields." where id = :taskid";
      // prepare the query
      $query = $writeDB->prepare($queryString);

      // if title has been provided
      if($title_updated === true) {
        // set task object title to given value (checks for valid input)
        $task->setTitle($jsonData->title);
        // get the value back as the object could be handling the return of the value differently to
        // what was provided
        $up_title = $task->getTitle();
        // bind the parameter of the new value from the object to the query (prevents SQL injection)
        $query->bindParam(':title', $up_title, PDO::PARAM_STR);
      }

      // if description has been provided
      if($description_updated === true) {
        // set task object description to given value (checks for valid input)
        $task->setDescription($jsonData->description);
        // get the value back as the object could be handling the return of the value differently to
        // what was provided
        $up_description = $task->getDescription();
        // bind the parameter of the new value from the object to the query (prevents SQL injection)
        $query->bindParam(':description', $up_description, PDO::PARAM_STR);
      }

      // if deadline has been provided
      if($deadline_updated === true) {
        // set task object deadline to given value (checks for valid input)
        $task->setDeadline($jsonData->deadline);
        // get the value back as the object could be handling the return of the value differently to
        // what was provided
        $up_deadline = $task->getDeadline();
        // bind the parameter of the new value from the object to the query (prevents SQL injection)
        $query->bindParam(':deadline', $up_deadline, PDO::PARAM_STR);
      }

      // if completed has been provided
      if($completed_updated === true) {
        // set task object completed to given value (checks for valid input)
        $task->setCompleted($jsonData->completed);
        // get the value back as the object could be handling the return of the value differently to
        // what was provided
        $up_completed= $task->getComplete();
        // bind the parameter of the new value from the object to the query (prevents SQL injection)
        $query->bindParam(':completed', $up_completed, PDO::PARAM_STR);
      }

      // bind the task id provided in the query string
      $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
      // run the query
    	$query->execute();

      // get affected row count
      $rowCount = $query->rowCount();

      // check if row was actually updated, could be that the given values are the same as the stored values
      if($rowCount === 0) {
        // set up response for unsuccessful return
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        $response->addMessage("Task not updated - given values may be the same as the stored values");
        $response->send();
        exit;
      }
      // ADD AUTH TO QUERY
      // create db query to return the newly edited task - connect to master database
      $query = $writeDB->prepare('SELECT id, title, descript, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from table_tasks where id = :taskid');
      $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
      $query->execute();

      // get row count
      $rowCount = $query->rowCount();

      // check if task was found
      if($rowCount === 0) {
        // set up response for unsuccessful return
        $response = new Response();
        $response->setHttpStatuseCode(404);
        $response->setSuccess(false);
        $response->addMessage("No task found");
        $response->send();
        exit;
      }
      // create task array to store returned tasks
      $taskArray = array();

      // for each row returned
      while($row = $query->fetch(PDO::FETCH_ASSOC)) {
        // create new task object for each row returned
        $task = new Task($row['id'], $row['title'], $row['descript'], $row['deadline'], $row['completed']);

        // create task and store in array for return in json data
        $taskArray[] = $task->returnTaskArray();
      }
      // bundle tasks and rows returned into an array to return in the json data
      $returnData = array();
      $returnData['rows_returned'] = $rowCount;
      $returnData['tasks'] = $taskArray;

      // set up response for successful return
      $response = new Response();
      $response->setHttpStatuseCode(200);
      $response->setSuccess(true);
      $response->addMessage("Task updated");
      $response->setData($returnData);
      $response->send();
      exit;

    } 
    catch(TaskException $ex){ 
      $response = new Response();
      $response->setHttpStatuseCode(404);
      $response->setSuccess(false);
      $response->addMessage("Task with the given id not found in the database.");
      $response->send();
      exit();
    }
    catch(PDOException $ex){
      error_log("Database querry error ".$ex, 0);
      $response = new Response();
      $response->setHttpStatuseCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to update ".$ex->getMessage());
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
      if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        $response->addMessage("Content type header is not set to json.");
        $response->send();
        exit();
      }
      $rawPostData = file_get_contents('php://input');
      if(!$jsonData = json_decode($rawPostData)){
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        $response->addMessage("Request data is not a valid Json.");
        $response->send();
        exit();
      }

      if(!isset($jsonData->title) || !isset($jsonData->completed)){
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        (!isset($jsonData->title)? $response->addMessage("Title field is mandatory and must be provided"):false);
        (!isset($jsonData->completed)? $response->addMessage("Completed field is mandatory and must be provided"):false);
        $response->send();
        exit();
      }

      $newTask = new Task(null,
          $jsonData->title,
          (isset($jsonData->descript) ? $jsonData->descript:null),
          (isset($jsonData->deadline)? $jsonData->deadline:null),
          $jsonData->completed);

      
      $title = $newTask->getTitle();
      $description = $newTask->getDescription();
      $deadline = $newTask->getDeadline();
      $completed = $newTask->getComplete();

      $query = $writeDB->prepare('insert into table_tasks (title, descript, deadline, completed) 
                values(:title, :descript, STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), :completed)');
      $query->bindParam(':title', $title, PDO::PARAM_STR);
      $query->bindParam(':descript', $description, PDO::PARAM_STR);
      $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
      $query->bindParam(':completed', $completed, PDO::PARAM_STR);
      $query->execute();

      $rowCount = $query->rowCount();
     
      if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatuseCode(400);
        $response->setSuccess(false);
        $response->addMessage("Failed to create tasks");
        $response->send();
        exit();
      }
      $lastInsertId = $writeDB->lastInsertId();

      $query = $writeDB->prepare('SELECT id, title, descript, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from table_tasks where id = :taskid');
      $query->bindParam(':taskid', $lastInsertId, PDO::PARAM_INT);
      $query->execute();

      $rowCount = $query->rowCount();
      if($rowCount===0){
        $response = new Response();
        $response->setHttpStatuseCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to retrieve ".$ex->getMessage());
        $response->send();
        exit();
      }
      $taskArray = array();
      while($row = $query->fetch(PDO::FETCH_ASSOC)){
        $task = new Task($row['id'],$row['title'], $row['descript'], $row['deadline'],$row['completed']);
        $taskArray[] = $task->returnTaskArray();
      }
      $returnData = array();
      $returnData['rows_returned'] = $rowCount;
      $returnData['tasks'] = $taskArray;

      $response = new Response();
      $response->setHttpStatuseCode(201);
      $response->setSuccess(true);
      $response->addMessage("Newly created task.");
      $response->setData($returnData);
      $response->send();
      exit();





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
