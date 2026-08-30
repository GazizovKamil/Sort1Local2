<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

require 'vendor/autoload.php';

$data = array(
    'buyer' => array(
        'name' => 'ООО «Ф.А. Логистик»',
        'address' => '125413, г. Москва, Солнечногорский проезд, д. 4',
        'inn' => '7743666731',
        'kpp' => '505345001',
        'anotherAdress' => 'ООО «Ф.А. Логистик» (ОП Электросталь ) , 144001, Московская обл., г. Электросталь, Строительный переулок, дом 9',
        'recipient' => 'ИП Иванов Иван Иванович, 140012, Московская обл., г. Жуковск, ул. Северная д1. стр.1',
    ),
    'seller' => array(
        'name' => 'ИП Иванов Иван Иванович',
        'address' => '142400, Московская обл., г. Жуковск, ул. Октябрьская, д.25 кв. 50',
        'inn' => '502232306751'        
    ),
    'goods' => array(
        array(
            'id' => 'AMD.EL447',
            'name' => array(
                '1' => 'AMD',
                '2' => 'AMD.EL447',
                '3' => 'Катушка зажигания',
                '4' => '25358034'
            ),
            'count' => '2',
            'price' => '547.67',
            'country' => array(
                'id' => '156',
                'title' => 'Китай'
            ),
            'regNum' => '10013160/160921/0571867'
        ),       
        array(
            'id' => '256-398',
            'name' => array(
                '1' => 'BOSAL',
                '2' => '256-398',
                '3' => 'Прокладка приемной трубы',
                '4' => '213705'
            ),
            'count' => '1',
            'price' => '97.50',
            'country' => array(
                'id' => '980',
                'title' => 'Евросоюз'
            ),
            'regNum' => '10418010/090821/0240580'
        ),       
        array(
            'id' => 'CEKH-48L',
            'name' => array(
                '1' => 'CTR',
                '2' => 'CEKH-48L',
                '3' => 'Наконечник рулевой тяги L (новый арт. CE0339L)',
                '4' => '2040108'
            ),
            'count' => '1',
            'price' => '733.33',
            'country' => array(
                'id' => '410',
                'title' => 'Корея, Республика'
            ),
            'regNum' => '10702070/171121/0375648'
        ),       
        array(
            'id' => 'CEKH-48R',
            'name' => array(
                '1' => 'CTR',
                '2' => 'CEKH-48R',
                '3' => 'Наконечник рулевой тяги R (новый арт. CE0339R)',
                '4' => '2040109'
            ),
            'count' => '1',
            'price' => '733.33',
            'country' => array(
                'id' => '410',
                'title' => 'Корея, Республика'
            ),
            'regNum' => '10702070/270821/0269592'
        ),       
        array(
            'id' => '5NB998002',
            'name' => array(
                '1' => 'VAG',
                '2' => '5NB998002',
                '3' => '1 к-т щеток стеклоочистителя',
                '4' => '24329233'
            ),
            'count' => '1',
            'price' => '1771',
            'country' => array(
                'id' => '056',
                'title' => 'Бельгия'
            ),
            'regNum' => '10131010/201021/0701978'
        )       
    )
);

$arr = [
    'января',
    'февраля',
    'марта',
    'апреля',
    'мая',
    'июня',
    'июля',
    'августа',
    'сентября',
    'октября',
    'ноября',
    'декабря'
  ];
  
  $month = date('n')-1;
  $currentDate = date('d').' '.$arr[$month].' '.date('Y').'г.';

  $schet = '070122-1266';

$inputFileName = 'peredatochny_doc.xls';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
$spreadsheet = $reader->load($inputFileName);
$sheet = $spreadsheet->getActiveSheet();

$sheet-> setCellValue("K2", $schet);
$sheet-> setCellValue("R2", $currentDate);
$sheet-> setCellValue("R11", "№ {$schet}            от {$currentDate}");

$sheet-> setCellValue("K4", $data['buyer']['name']);
$sheet-> setCellValue("K6", $data['buyer']['address']);
$sheet-> setCellValue("K7", $data['buyer']['inn'] . ' / ' . $data['buyer']['kpp']);
$sheet-> setCellValue("K8", $data['buyer']['anotherAdress']);
$sheet-> setCellValue("K9", $data['buyer']['recipient']);

$sheet-> setCellValue("K12", $data['seller']['name']);
$sheet-> setCellValue("K13", $data['seller']['address']);
$sheet-> setCellValue("K15", (string) $data['seller']['inn']);

$sheet-> setCellValue("J29", "Договор № 102130-26 от 10.11.2020г.");
$sheet-> setCellValue("A41", "{$data['buyer']['name']}, ИНН.КПП {$data['buyer']['inn']} / {$data['buyer']['kpp']}");
$sheet-> setCellValue("U41", "{$data['seller']['name']}, ИНН/КПП {$data['seller']['inn']}");
$sheet-> setCellValue("A45", "Универсальный передаточный документ № {$schet}");

$goods = $data['goods'];
$spreadsheet->getActiveSheet()->insertNewRowBefore(23, count($goods) - 1);

$arrTotalPrice = [];
$arrTax = [];
$arrTotalTax = [];

for ($i=0; $i < count($goods); $i++) { 
    $x = $i + 23;
    $y = $i + 1;
    $spreadsheet->getActiveSheet()->mergeCells("B{$x}:D{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("H{$x}:I{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("J{$x}:K{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("L{$x}:M{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("P{$x}:Q{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("R{$x}:S{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("T{$x}:V{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("W{$x}:X{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("Z{$x}:AB{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("AC{$x}:AE{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("AG{$x}:AH{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("AI{$x}:AJ{$x}");
    $spreadsheet->getActiveSheet()->mergeCells("AM{$x}:AN{$x}");
    $totalPrice = (float) $goods[$i]['count'] * (float) $goods[$i]['price'];
    $tax = $totalPrice / 100 * 20;
    $totalTax = $totalPrice + $tax;
    $sheet-> setCellValue("A{$x}", $y);
    $sheet-> setCellValue("B{$x}", $goods[$i]['id']);
    $sheet-> setCellValue("E{$x}", $y);
    $sheet-> setCellValue("F{$x}", $goods[$i]['name']['1']);
    $sheet-> setCellValue("G{$x}", $goods[$i]['name']['2']);
    $sheet-> setCellValue("H{$x}", $goods[$i]['name']['3']);
    $sheet-> setCellValue("J{$x}", $goods[$i]['name']['4']);
    $sheet-> setCellValue("L{$x}", '-');
    $sheet-> setCellValue("N{$x}", '796');
    $sheet-> setCellValue("O{$x}", 'ШТ');
    $sheet-> setCellValue("P{$x}", $goods[$i]['count']);
    $sheet-> setCellValue("R{$x}", $goods[$i]['price']);
    $sheet-> setCellValue("T{$x}", (string) round($totalPrice, 2));
    $sheet-> setCellValue("W{$x}", 'Без акциза');
    $sheet-> setCellValue("Y{$x}", '20%');
    $sheet-> setCellValue("Z{$x}", (string) round($tax, 2));
    $sheet-> setCellValue("AC{$x}", (string) round($totalTax, 2));
    $sheet-> setCellValue("AF{$x}", $goods[$i]['country']['id']);
    $sheet-> setCellValue("AG{$x}", $goods[$i]['country']['title']);
    $sheet-> setCellValue("AI{$x}", $goods[$i]['regNum']);
    array_push($arrTotalPrice, round($totalPrice, 2));
    array_push($arrTax, round($tax, 2));
    array_push($arrTotalTax, round($totalTax, 2));    
}

$indexCell = count($goods) + 24;
$sheet-> setCellValue("T{$indexCell}", array_sum($arrTotalPrice));
$sheet-> setCellValue("Z{$indexCell}", array_sum($arrTax));
$sheet-> setCellValue("AC{$indexCell}", array_sum($arrTotalTax));

$writer = new Xls($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="peredatochny_doc.xls"');
$writer->save('php://output');

?>