<?php
define("TOKEN", "YOUR_BOT_TOKEN"); //bot token
define("FRAME", "frame.png"); //relative path to frame png file
define("URL", "URL_TO_IMAGE_FOLDER"); //link to the folder that contains the image (https://your-site.com/your-path-to-image-folder/)
define("FILE_IMAGE", "image".uniqid().".png"); //relative path to the image that will be created

//gets telegram request
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if(!$update){
  exit;
}

$message = isset($update['message']) ? $update['message'] : "";
$chatId = isset($message['chat']['id']) ? $message['chat']['id'] : "";

//function to execute telegram api requests in url POST mode. Returns the telegram response as a json file
function execurl($method, $params){ //$method - string - name of the method  |  $params - array - parameters of the request
    $ch1 = curl_init();
    curl_setopt($ch1, CURLOPT_URL, "https://api.telegram.org/bot" . TOKEN . "/$method");
    curl_setopt($ch1, CURLOPT_POST, true);
    curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch1);
    curl_close($ch1);
    return $result;
}

if($message['photo'][0]['file_id'] != ""){ //if a photo has been sent
  $i = count($message['photo']) - 1;

  //download the photo in the highest quality
  $photo = json_decode(execurl("getFile",array('file_id' => $message['photo'][$i]['file_id'])),true);

  $filephoto = "https://api.telegram.org/file/bot" .TOKEN. "/" . $photo['result']['file_path'];
  $image = file_get_contents($filephoto, true);

  //create image file
  $f_image = fopen(FILE_IMAGE, "w");
  fwrite($f_image, $image);
  fclose($f_image);

  //create image
  $stamp = imagecreatefrompng(FRAME);
  $im = imagecreatefromjpeg(FILE_IMAGE);

  //get smallest side
  $min = imagesx($im);
  if(imagesy($im) < $min){
    $min = imagesy($im);
  }

  //crop the image in a square with side lenght = smallest side and centered
  $im = imagecrop($im, ['x' => abs(floor(imagesx($im)-$min)/2), 'y' => abs(floor(imagesy($im)-$min)/2), 'width' => $min, 'height' => $min]);

  //copy frame onto the image
  imagecopyresized($im, $stamp, 0, 0, 0, 0, imagesx($im), imagesy($im),imagesx($stamp), imagesy($stamp));

  //writes the image to file
  imagepng($im, FILE_IMAGE);

  //send photo
  execurl("sendPhoto",array(
    'chat_id' => $chatId, 
    'photo' => URL . FILE_IMAGE
  ));

  //delete image and file
  imagedestroy($im);
  unlink(FILE_IMAGE);
}
else{ //welcome message
  if($message['from']['language_code'] == "it"){
    execurl("sendMessage",array(
      'chat_id' => $chatId, 
      'parse_mode' => 'html', 
      'text' => "<b>Ciao ".$message['chat']['first_name']."!</b>

Inviami una foto e ti creerò un'immagine profilo con una cornice"
  ));
  }
  else{
    execurl("sendMessage",array(
      'chat_id' => $chatId, 
      'parse_mode' => 'html', 
      'text' => "<b>Welcome ".$message['chat']['first_name']."</b>

Please send me a photo and I will create a profile picture with a frame"
  ));
  }
}
?>
