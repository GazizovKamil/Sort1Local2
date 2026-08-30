<?php

function draw_axises($im_width,$im_heignt)
{
global $im,$black,$l_grey,$x0,$y0,$maxX,$maxY;
$x0=45.0; //начало оси координат по X
$y0=20.0; //начало оси координат по Y
$maxX=$im_width-$x0; //максимальное значение оси
//координат по X в пикселах
$maxY=$im_heignt-$y0; //максимальное значение оси
//координат по Y в пикселах
//рисуем ось X
imageline($im, $x0, $maxY, $maxX, $maxY, $black);
//рисуем ось Y
imageline($im, $x0, $y0, $x0, $maxY, $black);
//рисуем стрелку на оси X
$xArrow[0]=$maxX-6; $xArrow[1]=$maxY-2;
$xArrow[2]=$maxX; $xArrow[3]=$maxY;
$xArrow[4]=$maxX-6; $xArrow[5]=$maxY+2;
imagefilledpolygon($im, $xArrow, 3, $black);
//рисуем стрелку на оси Y
$yArrow[0]=$x0-2; $yArrow[1]=$y0+6;
$yArrow[2]=$x0; $yArrow[3]=$y0;
$yArrow[4]=$x0+2; $yArrow[5]=$y0+6;
imagefilledpolygon($im, $yArrow, 3, $black);
}

function draw_grid($xStep,$yStep,$xCoef,$yCoef)
{
   global $im,$black,$l_grey,$x0,$y0,$maxX,$maxY;
   $xSteps=($maxX-$x0)/$xStep-1; //определяем количество
   //шагов по оси X
   $ySteps=($maxY-$y0)/$yStep-1; //определяем количество
   //шагов по оси Y
   //выводим сетку по оси X
   for($i=1;$i<$xSteps+1;$i++){
      imageline($im, $x0+$xStep*$i, $y0, $x0+$xStep*$i,$maxY-1, $l_grey);
      //при необходимости выводим значения линий сетки по оси X
      //imagestring($im, 1, ($x0+$xStep*$i)-1, $maxY+2, $i*$xCoef, $black);
      imagestring($im, 1, ($x0+$xStep*$i)-1, $maxY+2, $i, $black);
   }
   //выводим сетку по оси Y
   for($i=1;$i<$ySteps+1;$i++){
      imageline($im, $x0+1, $maxY-$yStep*$i, $maxX,
      $maxY-$yStep*$i, $l_grey);
      //при необходимости выводим значения линий сетки по оси Y
      imagestring($im, 1, 0, ($maxY-$yStep*$i)-3, $i*$yCoef, $black);
   }
}

function draw_data($data_x,$data_y,$points_count,$color)
{
   global $im,$x0,$y0,$maxY,$scaleX,$scaleY;
   for($i=1;$i<$points_count;$i++){
      //рисуем линейный график по точкам из массивов данных
      imageline($im, $x0+$data_x[$i-1]*$scaleX, $maxY-$data_y[$i-1]*$scaleY-1,
            $x0+$data_x[$i]*$scaleX, $maxY-$data_y[$i]*$scaleY-1,$color);
      imageline($im, $x0+$data_x[$i-1]*$scaleX, $maxY-$data_y[$i-1]*$scaleY,
            $x0+$data_x[$i]*$scaleX, $maxY-$data_y[$i]*$scaleY,$color);
      imageline($im, $x0+$data_x[$i-1]*$scaleX, $maxY-$data_y[$i-1]*$scaleY+1,
            $x0+$data_x[$i]*$scaleX, $maxY-$data_y[$i]*$scaleY+1,$color);
   }
}
$dat = file_get_contents('php://input');
if ($dat != '') {
    $json=json_decode($dat,true);
}
//print_r($json);
//создаем рисунок шириной 500 и высотой 400 пикселов
$im = @ImageCreate(900, 400);
$white = ImageColorAllocate ($im, 255, 255, 255);
$black = ImageColorAllocate ($im, 0, 0, 0);
$red = ImageColorAllocate ($im, 255, 0, 0);
$green = ImageColorAllocate ($im, 0, 255, 0);
$blue = ImageColorAllocate ($im, 0, 0, 255);
$yellow = ImageColorAllocate ($im, 255, 255, 0);
$magenta = ImageColorAllocate ($im, 255, 0, 255);
$cyan = ImageColorAllocate ($im, 0, 255, 255);
$l_grey = ImageColorAllocate ($im, 221, 221, 221);
//рисуем оси координат
draw_axises(900,400);
//задаем массивы данных графиков
$x1=$json['x1']; $y1=$json['y1']; // 1 fakt, 2 daily_plan, 3 expected_fact
$x2=$json['x2']; $y2=$json['y2'];
$x3=$json['x3']; $y3=$json['y3'];
//объединяем данные из массивов данных
//для вычисления масштаба
$x=array_merge($x1);
$y=array_merge($y1,$y2,$y3);
//print_r($x);
//получаем максимальные значения
//элементов для каждого массива
$maxXVal=max($x);
$maxYVal=max($y);
//вычисляем масштаб преобразования данных
//в координаты рабочей области
$scaleX=($maxX-$x0)/$maxXVal;
$scaleY=($maxY-$y0)/$maxYVal;
//задаем шаг для координатной сетки в пикселах
$xStep=count($x1);
$yStep=30;
//рисуем координатную сетку
draw_grid($xStep,$yStep,
    round($xStep/$scaleX,1),
    round($yStep/$scaleY,1),
    true);
//рисуем описание
imageline($im, 60, 65, 75, 65, $red);
//imagestring($im, 1, 80, 60, "Plan", $black);
imagettftext($im,9,0,80,70,$black,"/var/www/sort1pro/arial.ttf","План");
imageline($im, 120, 65, 135, 65, $blue);
//imagestring($im, 1, 130, 60, "Fact", $black);
imagettftext($im,9,0,140,70,$black,"/var/www/sort1pro/arial.ttf","Факт");
imageline($im, 180, 65, 195, 65, $green);
//imagestring($im, 1, 180, 60, "Exp. fact", $black);
imagettftext($im,9,0,200,70,$black,"/var/www/sort1pro/arial.ttf","Ожидаемый факт");

//рисуем второй график
draw_data($x2,$y2,30,$red);
draw_data($x3,$y3,30,$green);
//рисуем первый график
draw_data($x1,$y1,30,$blue);
//выводим рисунок
//Header("Content-Type: image/png");
//ImagePNG($im);
session_start();
$id = $_SESSION['user_id']; //Whereas this generates a random ID number
$file="/tmp/testimage".$id.".png";
imagepng($im, $file);
imagedestroy($im);
echo(base64_encode(file_get_contents($file)));
unlink($file);
//освобождаем занимаемую рисунком память
//imagedestroy($im);
?>