<?php
$_SERVER['REQUEST_METHOD']='GET';
ini_set('session.save_path',sys_get_temp_dir());
require dirname(__DIR__).'/app/bootstrap.php';
foreach (['5511999991234','351912345678'] as $number) {
 $svg=simplexml_load_string(base64_decode(explode(',',qr($number),2)[1]));
 $width=(int)(string)$svg['width'];
 $image=imagecreatetruecolor($width,$width);
 imagefill($image,0,0,imagecolorallocate($image,255,255,255));
 $black=imagecolorallocate($image,0,0,0);
 // Rasterização dos retângulos do SVG realmente retornado pela aplicação.
 preg_match_all('/M(\d+),(\d+)L(\d+),(\d+)L(\d+),(\d+)L(\d+),(\d+)Z/',(string)$svg->path['d'],$rectangles,PREG_SET_ORDER);
 if (!$rectangles) throw new RuntimeException('SVG sem módulos reconhecidos.');
 foreach($rectangles as $r) imagefilledrectangle($image,(int)$r[1],(int)$r[2],(int)$r[5]-1,(int)$r[6]-1,$black);
 $reader=new Zxing\QrReader($image,Zxing\QrReader::SOURCE_TYPE_RESOURCE,false);
 if ($reader->text()!=='https://wa.me/'.$number) throw new RuntimeException('QR Code não decodificado corretamente.');
 echo 'PASS QR SVG decodificado: https://wa.me/'.$number.PHP_EOL;
}
