<?php
if(!function_exists('mime_content_type')) {

    function mime_content_type($filename) {

        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.',$filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
    }
}

function imageresize($outfile,$infile,$percents,$quality) {
    $im=imagecreatefromjpeg($infile);
    $w=imagesx($im)*$percents/100;
    $h=imagesy($im)*$percents/100;
    $im1=imagecreatetruecolor($w,$h);
    imagecopyresampled($im1,$im,0,0,0,0,$w,$h,imagesx($im),imagesy($im));

    imagejpeg($im1,$outfile,$quality);
    imagedestroy($im);
    imagedestroy($im1);
    }

//imageresize("","webcam.jpg",30,75);

function file_force_download($file,$filename,$small=1) {
  if (file_exists($file)) {
    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
    // если этого не сделать файл будет читаться в память полностью!
    if (ob_get_level()) {
      ob_end_clean();
    }
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: '.mime_content_type($file));
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
//    if($small) header('Content-Length: ' . filesize($file."_small"));
    // читаем файл и отправляем его пользователю
    $is_jpeg=0;
    if(preg_match("/.+\.jpg/",$filename) || preg_match("/.+\.jpeg/",$filename)) $is_jpeg=1;
    if($is_jpeg) imageresize($file."_small",$file,30,75);
    if($small && $is_jpeg) readfile($file."_small");
    else readfile($file);
    exit;
  }
}

include "include/db.inc.php";
include "include/db_safe.inc.php";
$db = new SafeMySQL(['mysqli' => $mysqli]);
$file_id=$_GET['file_id'];
session_start();
if(!isset($_SESSION['user_id'])) {
    echo "not auth";
    exit(0);
}
$user_id=$_SESSION['user_id'];
$roles=$_SESSION['roles'];

session_write_close();
$mfile_data=$db->getRow("select * from bug_files where id=?i",(int)$file_id);
//echo print_r($mfile_data,true)."\n";
$permition=$db->getOne("select id from bugs where user_id=?i and id=?i",$user_id,(int)$mfile_data['bug_id']);
//echo "user_id=$user_id, perm=$permition, roles=$roles\n";
if((int)$permition>0 || $roles==1){
    if(isset($_GET['full']) && (int)$_GET['full']==1)
	file_force_download(dirname($_SERVER['SCRIPT_FILENAME']).'/api/support_files/'.$mfile_data['local_filename'],$mfile_data['file_name'],0);
    else
	file_force_download(dirname($_SERVER['SCRIPT_FILENAME']).'/api/support_files/'.$mfile_data['local_filename'],$mfile_data['file_name']);
    echo "file_name=".dirname($_SERVER['SCRIPT_FILENAME']).'/api/support_files/'.$mfile_data['local_filename'];
}
?>
