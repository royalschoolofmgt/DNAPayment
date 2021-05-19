<?php
   $data = file_get_contents('php://input');
   $fp = fopen("webhook.txt", "w");
   fwrite($fp, $data);
   fclose($fp);
   
   $data = $_REQUEST;
   $fp = fopen("webhook1.txt", "w");
   fwrite($fp, $data);
   fclose($fp);

?>