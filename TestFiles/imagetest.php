<?php

require_once('image.php');

try {
    $image = new Image(1, "Title Here", "File Name Here", "image/jpeg", 2);
    header('Content-type: application/json;charset=UTF-8');
    echo json_encode($image->returnImageAsArray());
}
catch(ImageException $ex) {
    echo "Error: ".$ex->getMessage();
}