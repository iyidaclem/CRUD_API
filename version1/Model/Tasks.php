<?php

class TaskException extends Exception{}

class Task{

  private $_id;
  private $_title;
  private $_description;
  private $_deadline;
  private $_complete;

  public function __construct($id, $title, $description, $deadline, $complete){
    $this->setID($id);
    $this->setTitle($title);
    $this->setDescription($description);
    $this->setDeadline($deadline);
    $this->setCompleted($complete);
  }
 

  public function getID(){
    return $this->_id;
  }

  public function getTitle(){
    return $this->_title;
  }

  public function getDescription(){
    return $this->_description;
  }

  public function getDeadline(){
    return $this->_deadline;
  }

  public function getComplete(){
    return $this->_complete;
  }

  // public function setID($id){
  //   if(($id !== null) && (!is_numeric($id) || $id <= 0|| $id>9223372036854775807 || $this->_id !== null)){
  //     throw new TaskException("Tasks ID error");
  //   }
  //   return $this->_id = $id;
  // }
  public function setID($id) {
		// if passed in task ID is not null or not numeric, is not between 0 and 9223372036854775807 (signed bigint max val - 64bit)
		// over nine quintillion rows
		if(($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->_id !== null)) {
			throw new TaskException("Task ID error");
		}
		$this->_id = $id;
	}
 
  public function setTitle($title){
    if(strlen($title) <0 || strlen($title) >255){
      throw new TaskException("Tasks title error");
    }
    $this->_title = $title;
  }

  public function setDescription($description){
    if(($description !== null) && (strlen($description)>16777215)){
      throw new TaskException("Tasks description error.");
    }

    $this->_description = $description;
  }

  public function setDeadline($deadline){
    if($deadline !== null) {
			if(!date_create_from_format('d/m/Y H:i', $deadline) || date_format(date_create_from_format('d/m/Y H:i', $deadline), 'd/m/Y H:i') != $deadline) {
				throw new TaskException("Task deadline date and time error");
			}
			$this->_deadline = $deadline;
    	}
  }

  public function setCompleted($completed){
    if(strtoupper($completed) !== "Y" && strtoupper($completed) !== "N"){
      throw new TaskException("Task completed must be Y or N.");
    }
    $this->_complete = $completed;
  }

  public function returnTaskArray(){
    $task = array();
    $task['id'] = $this->getID();
    $tasks['title'] = $this->getTitle();
    $tasks['description'] = $this->getDescription();
    $tasks['deadline'] = $this->getDeadline();
    $tasks['complete'] = $this->getComplete();
    return $tasks;
  }
}