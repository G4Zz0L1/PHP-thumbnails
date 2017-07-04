<?php
ini_set("memory_limit", "1G");
require_once 'class.upload.php/src/class.upload.php';
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
   $new_name = md5(trim($_GET['file'])) . "_w" . trim($_GET['width']) . "_h" . trim($_GET['height']);
   $fold_name = "cache/" . $new_name;
   $handle->file_new_name_body = $new_name;
   $temp = glob($fold_name . "*");
   $filename = $fold_name . "." . $handle->file_src_name_ext;
   if (function_exists("exif_read_data"))
   {
      $exif = exif_read_data($_GET['file']);
      if (strlen(trim($exif['Orientation'])) > 0 && $exif['Orientation'] > 0)
      {
         // TODO: correct wrong orientation
         switch ($exif['Orientation'])
         {
            // Correct orientation, but flipped on the horizontal axis
            case 2:
               $handle->image_flip = 'h';
               break;
            // Upside-Down
            case 3:
               $handle->image_flip = 'v';
               break;
            // Upside-Down & Flipped along horizontal axis
            case 4:
               $handle->image_flip = 'h';
               $handle->image_flip = 'v';
               break;
            // Turned 90 deg to the left and flipped
            case 5:
               $handle->image_flip = 'h';
               $handle->image_rotate = 270;
               break;
            // Turned 90 deg to the left
            case 6:
               $handle->image_rotate = 270;
               break;
            // Turned 90 deg to the right and flipped
            case 7:
               $handle->image_flip = 'h';
               $handle->image_rotate = 90;
               break;
            // Turned 90 deg to the right
            case 8:
               $handle->image_rotate = 90;
               break;
         }
      }
   }
   if (!isset($temp[0]) || !file_exists($temp[0]) || $original_time > filemtime($filename))
   {
      if (file_exists($filename))
      {
         unlink($filename);
      }
      $handle->Process("cache/");
      if (!$handle->processed)
      {
         echo 'error : ' . $handle->error;
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
         echo 'error : ' . $handle->error;
      }
   }
   else
   {
      echo 'error : ' . $handle->error;
   }
}
else
{
   echo 'error : ' . $handle->error;
}
exit();
?>