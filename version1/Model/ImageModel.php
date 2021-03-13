<?php

class ImageException extends PDOException{}

class Image{
  private $_id;
  private $_title;
  private $_filename;
  private $_mimetype;
  private $_taskid;
  private $_uploadFolderLocation;

  public function __construct($id, $title, $filename, $mimetyp, $taskid, $uploadfolderLocation){
    $this->setID($id);
    $this->setTitle($title);
    $this->setFilename($filename);
    $this->setMimeType($mimetyp);
    $this->setTaskID($taskid);
    $this->_uploadFolderLocation = "../../task_images";
  }


  public function getID(){
    return $this->_id;
  }

  public function getTitle(){
    return $this->_title;
  }

  public function getFileExtension(){
    $_filename_parts = explode(".", $this->_filename);
    $lastArrayElement = count($_filename_parts)-1;
    $fileExtension = $_filename_parts($lastArrayElement);
    return $fileExtension;
  }

  public function getMimeType(){
    return $this->_mimetype;
  }

  public function getTaskID(){
    return $this->_taskid;
  }

  public function getUploadFileLocation(){
    return $this->_uploadFolderLocation;
  }

  public function getFilename() {
		return $this->_filename;
	}

  public function getImageURL(){
    $httpOrHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ==='on'?"https":"http");
    $host = $_SERVER['HTTP_HOST'];
    $url = "/version1/tasks/".$this->getTaskID()."/images/".$this->getID();
    return $httpOrHttps."://".$host.$url;
  }

  public function setID($id){
    if(($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->_id !== null)) {
			throw new ImageException("Image ID error");
		}
		$this->_id = $id;
  }

  public function setTitle($title){
    if(strlen($title) <1 || strlen($title) >255){
      throw new ImageException("Image title error.");
    }
  }

  public function setFilename($filename){
    if(strlen($filename) < 1 || strlen($filename) > 30 || preg_match("/^[a-zA-Z0-9_-]+(.jpg|.gif|.png)$/", $filename) != 1) {
			throw new ImageException("Image filename error - must be between 1 and 30 characters long and only contain alphanumeric, underscore, hyphen, no spaces and have a .jpg, .gif or a .png file extension");
		}
		$this->_filename = $filename;
  }

  public function setMimeType($mimetyp){
    if(strlen($mimetyp)<1 || strlen($mimetyp)>255){
      throw new ImageException("Image mimetype error.");
    }
    $this->_mimetype = $mimetyp;
  }

  public function setTaskID($taskid){
    if(($taskid !== null) && (!is_numeric($taskid) || $taskid <= 0 || $taskid > 9223372036854775807 || $this->_taskid !== null)) {
			throw new ImageException("Image taskid error");
		}
		$this->_taskid = $taskid;   
  }


  public function returnImageAsArray(){
    $image = array();
    $image['id'] = $this->getID();
    $image['title'] = $this->getTitle();
    $image['filename'] = $this->getFilename();
    $image['mimetype'] = $this->getMimeType();
    $image['mimetype'] = $this->getMimeType();
    $image['taskid'] = $this->getTaskID();
    $image['imageurl'] = $this->getImageURL();

    return $image;
  }
}