<?php
ini_set("memory_limit", "1G");
require_once 'class.upload.php/src/class.upload.php';
if (isset($_GET['arg']) && strlen(trim($_GET['arg'])) > 0)
{
   $count = explode("/", trim($_GET['arg']));
   $_GET['file'] = "";
   foreach ($count as $k => $v)
   {
      $_GET['file'] .= $v . "/";
      if (!file_exists($_GET['file']))
      {
         if (!isset($_GET['width']) && isset($count[$k]) && strlen(trim($count[$k])) > 0 && is_numeric($count[$k]))
         {
            $_GET['width'] = $count[$k];
            $_GET['file'] = "";
            continue;
         }
         if (!isset($_GET['height']) && isset($count[$k]) && strlen(trim($count[$k])) > 0 && is_numeric($count[$k]))
         {
            $_GET['height'] = $count[$k];
            $_GET['file'] = "";
            continue;
         }
      }
      else if (strlen(trim(pathinfo($_GET['file'], PATHINFO_EXTENSION))) > 0)
      {
         break;
      }
   }
   $_GET['file'] = rtrim($_GET['file'], "/");
}
$_GET['width'] = isset($_GET['width']) && strlen(trim($_GET['width'])) > 0 && is_numeric($_GET['width']) ? $_GET['width'] : 250;
$_GET['height'] = isset($_GET['height']) && strlen(trim($_GET['height'])) > 0 && is_numeric($_GET['height']) ? $_GET['height'] : 250;
$handle = new upload(trim($_GET['file']));
$original_time = filemtime(trim($_GET['file']));
if ($handle->error != "")
{
   $handle->clean();
   $handle = new upload("images/logo.png");
}
if (!file_exists("cache") || !is_dir("cache"))
{
   $oldmask = umask(0);
   mkdir("cache", 0777);
   umask($oldmask);
}
$files = glob('cache/*.*');
array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_ASC, $files);
if (isset($files[0]) && (time() - filemtime($files[0])) > (60 * 60 * 24 * 7))
{
   foreach ($files as $value)
   {
      unlink($value);
   }
}
if ($handle->uploaded)
{
   $handle->image_resize = true;
   $handle->image_x = trim($_GET['width']);
   $handle->image_y = trim($_GET['height']);
   $handle->image_ratio = true;
   $handle->image_ratio_no_zoom_in = true;
   $new_name = md5(trim($_GET['file'])) . "-w" . trim($_GET['width']) . "-h" . trim($_GET['height']);
   $fold_name = "cache/" . $new_name;
   $handle->file_new_name_body = $new_name;
   $temp = glob($fold_name . "*");
   $filename = $fold_name . "." . $handle->file_src_name_ext;
   if (!isset($temp[0]) || !file_exists($temp[0]) || $original_time > filemtime($filename))
   {
      if (file_exists($filename))
      {
         unlink($filename);
      }
      $handle->Process("cache/");
      if (!$handle->processed)
      {
         echo 'error 1: ' . $handle->error;
      }
   }
   $handle = new upload($filename);
   if ($handle->uploaded)
   {
      header('Content-type: ' . $handle->file_src_mime);
      header('Content-Length: ' . filesize($filename));
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($filename)));
      header('ETag: ' . $new_name);
      header('Accept-Ranges: none');
      if (filemtime($filename) > $original_time)
      {
         header('Cache-Control: max-age=604800, must-revalidate');
         header('Expires: ' . gmdate('D, d M Y H:i:s T', strtotime('+7 days')));
      }
      else
      {
         header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
         header('Expires: ' . gmdate('D, d M Y H:i:s T'));
         header('Pragma: no-cache');
      }
      echo $handle->Process();
      if (!$handle->processed)
      {
         echo 'error 2: ' . $handle->error;
      }
   }
   else
   {
      echo 'error 3: ' . $handle->error;
   }
}
else
{
   echo 'error 4: ' . $handle->error;
}
exit();
?>