<?php
header('Content-type: image/png');
$regusers = array(20, 26, 900, 110, 14, 25, 10, 34,56);
$segments_x = count($regusers); //количество сегментов
$lengthsegment_x = floor((570-50)/($segments_x - 1)); //длина сегмента
$max = max($regusers);
$min = min($regusers);
$lengthsegment_y = floor((250-50)/($max - $min));
$k=1;
while($lengthsegment_y<=0){
    $lengthsegment_y = floor((250-50)/(($max - $min)/(10*$k)));
    $k++;
}
if($k==1) $k=1/10;
if($k==2) $k=1/1.02;
if($k==3) $k=1.1;
if($k<5 && $k>=2) $k=1/(10/$k);
$image = imageCreateTrueColor(600, 280);
$white = imageColorAllocate($image, 255, 255, 255);
imagefilledrectangle($image, 0, 0, 599, 279, $white);
$blue = imageColorAllocate($image, 0, 0, 255);
imageLine($image, 50, 250, 570, 250, $blue);
imageLine($image, 50, 50, 50, 250, $blue);
for ($i=1; $i<=$segments_x; $i++)
{
  $x = 50 + $lengthsegment_x * ($i - 1);
  if ($i > 1)
  imageLine($image, $x, 246, $x, 249, $blue);
  imagestring($image, 5, $x-1, 255, "$i", $blue);
}
$y = 250 - $lengthsegment_y * (($max - $min)/(10*$k));
imageLine($image, 51, $y, 54, $y, $blue);
$num = $max;
imageString($image, 1, 10, $y-5, "$num", $blue);
$y = 250 - $lengthsegment_y * floor(($max-$min)/(10*$k) / 2);
imageLine($image, 51, $y, 54, $y, $blue);
$num = $min + floor(($max - $min) / 2);
imageString($image, 1, 10, $y-5, "$num", $blue);
$num = $min;
imageString($image, 1, 10, 241, "$num", $blue);
$num = $max;
//imageString($image, 1, 30, $y-5, "$num", $blue);
$i=0;
//for($j=$lengthsegment_y; $j<200; $j+=$lengthsegment_y){
  //  $y = 50 + $j;
    //imageLine($image, 51, $y, 54, $y, $blue);
    //if($i==0 || !($i%5) ) imageString($image, 1, 30, $y-5, $num, $blue);
   // $num -= 1;//$lengthsegment_y;
   // $i++;
//}
//$num = $min;
//imageString($image, 1, 30, 241, "$num", $blue);
$green = imageColorAllocate($image, 50, 237, 35);
imageSetThickness($image, 2);
for ($i=0; $i<=$segments_x-1; $i++)
{
  $x1 = 50 + $i * $lengthsegment_x;
  $y1 = 250 - ($regusers[$i] - $min)/(10*$k) * $lengthsegment_y;
  imageString($image, 2, 60, 15+$i*15, "$x1 $y1 - $x2 $y2 ($lengthsegment_y, $segments_x, k=$k)", $blue);
  if ($i>0)
    imageLine($image, $x1, $y1, $x2, $y2, $green);
  $x2 = $x1;
  $y2 = $y1;
  
}
imagepng($image);
imagedestroy($image);
?>