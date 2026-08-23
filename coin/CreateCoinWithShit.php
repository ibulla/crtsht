<?php
include("../inc/verbindungNeu.php");

for($i=101;$i<129;$i ++){

  $query = sprintf("SELECT * FROM `ShitID` WHERE `ID` = '%s'", mysqli_real_escape_string($mysqli, $i));
  $src = mysqli_query($mysqli,$query);
  	if($src->num_rows >=1){
  	$inhalt = $src->fetch_object();
  	$ID		    	= $inhalt->ID;
  	$ShitID		  = $inhalt->ShitID;
  	$Bildpfad	  = $inhalt->Bildpfad;
  	$BTC_Ad		  = $inhalt->BTC_Adr;
  	$ETH_Ad		  = $inhalt->ETH_Adr;
  	$Hash_Print	= $inhalt->Hasher_Druck;
  	$Whirlpool	= $inhalt->Whirlpool;
    $Login		  = $inhalt->LoginWhirlpool;
  	}

$POO = substr(hash("whirlpool",$Login.".never.fiber.inside.level"),0,17);
$ShortWhirl = substr($Whirlpool,0,5);
$SafeWhirl = substr($Whirlpool,0,3);

  $imgPath = 'Coin-mit-Fenster.jpg'; //'TheCoin.jpg';
  $imgOverlay = "../shitpix_jpg_1-128/".$i."-".$ShortWhirl.".jpg";
  $imgOver = imagecreatefromjpeg($imgOverlay);

  $image = imagecreatefromjpeg($imgPath);
  $color = imagecolorallocate($image, 255, 255, 255);

  $over_w = 800;
  $over_h = 800;
  $overpos_x = 0;
  $overpos_y = 0;
  $dest_w = 146;
  $dest_h = 146;
  $dest_x = 459;
  $dest_y = 128;

  imagecopyresized($image,$imgOver,$dest_x,$dest_y,$overpos_x,$overpos_y,$dest_w,$dest_h,$over_w,$over_h);

//  $string = "0x".sprintf('%04x', bindec(decbin($i)));
  $string = $ShitID;
  $fontfile = "RobotoMono-VariableFont_wght.ttf";
  $x = 450;
  $y = 773;
  $path = "numcoin-with/".$i."-".$POO.".jpg";
  imagettftext($image,36,0,$x,$y,$color,$fontfile,$string);

  $string1 = $ETH_Ad;
  $x1 = 246;
  $y1 = 528;
  imagettftext($image,18,0,$x1,$y1,$color,$fontfile,$string1);

  $hash = str_split($Hash_Print, 42);
  $string2 = $hash[0];
  $x1 = 246;
  $y1 = 550;
  imagettftext($image,18,0,$x1,$y1,$color,$fontfile,$string2);
  $string2 = $hash[1]."  |".$POO;
  $x1 = 246;
  $y1 = 572;
  imagettftext($image,18,0,$x1,$y1,$color,$fontfile,$string2);


  imagejpeg($image,$path);
  echo "<img src='".$path."' height='350px'>";
}


?>
