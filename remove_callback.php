<?php
   ////$data = json_decode(file_get_contents('php://input'), true);
   $data = $_REQUEST;
   $fp = fopen("remove.txt", "w");
   fwrite($fp, serialize($data));
   fclose($fp);

?>