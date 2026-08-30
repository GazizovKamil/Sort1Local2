<?php
if(isset($_GET['image'])) {
    $imageName = $_GET['image'];
    if(preg_match('/^[a-zA-Z0-9А-Яа-я]{1}/u', $imageName)) { // Проверяем, что переменная $imageName содержит только буквы, цифры и символы Unicode
        if (preg_match("/^[а-яА-Я]{1}/u", $imageName)) {
			$article_folder = strtoupper(substr($imageName, 0, 1));
		} else if(preg_match("/^[a-zA-Z0-9]{1}/u", $imageName)) {
			$article_folder = strtoupper(substr($imageName, 0, 2));
		}
        $imagePath = '/var/www/library_images/'.$article_folder.'/'.$imageName;

        if(file_exists($imagePath)){
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
            $mime_type = '';
            if($extension === 'jpg' || $extension === 'jpeg') {
                $mime_type = 'image/jpeg';
            } elseif($extension === 'png') {
                $mime_type = 'image/png';
            } elseif($extension === 'gif') {
                $mime_type = 'image/gif';
            }
            if($mime_type !== '') {
                header('Content-Type: '.$mime_type);
                readfile($imagePath);
                exit();
            }
        } else {
            echo '';
        }
    } else {
        echo '';
    }
}
echo '';
?>