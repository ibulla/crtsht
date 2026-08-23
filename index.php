<?php

include_once("inc/verbindungNeu.php");
include_once("inc/verbindungUSER.php");

/***********************/
//https://api.etherscan.io/api?module=account&action=tokennfttx&address=".$ETH_ADDR."&startblock=12548000&endblock=999999999&sort=asc&apikey=FYPV6M36Q44QQI2Y1GRUDT5VWZET1AK9U2
//https://min-api.cryptocompare.com/data/price?fsym=ETH&tsyms=USD
function GET_URL_ANTWORT($pfad){
		$headers = array();

		// our curl handle (initialize if required)
		static $ch = null;
		if (is_null($ch))
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT,
				'Mozilla/4.0 (compatible; MtGox PHP Client; ' . php_uname('s') . '; PHP/' .
				phpversion() . ')');
		}
		curl_setopt($ch, CURLOPT_URL, $pfad);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // man-in-the-middle defense by verifying ssl cert.
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // man-in-the-middle defense by verifying ssl cert.
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$res = curl_exec($ch);
		if ($res === false){
			return "PROBLEM: ".curl_error($ch);
			}else{
			return $res;
				 }
}

/***********************/

function ETH_PRICE_TO_USD($eth){
  $url  = "https://min-api.cryptocompare.com/data/price?fsym=ETH&tsyms=USD";
  $response = GET_URL_ANTWORT($url);

  if (!is_string($response) || str_starts_with($response, "PROBLEM:")) {
    return "0.00";
  }

  $ok = json_decode($response, true);
  if (!is_array($ok) || !isset($ok["USD"])) {
    return "0.00";
  }

  $result = (float)$ok["USD"];
  return number_format(($result * (float)$eth), 2, '.', '');
}

/***********************/

function Verkauft_Klasse($ID){
global $mysqli_USER;
$ausgabe = "";
$query = sprintf("SELECT * FROM `Burned` WHERE `OnlyRare` = '%s'", mysqli_real_escape_string($mysqli_USER, $ID));
$src = mysqli_query($mysqli_USER,$query);
	if($src->num_rows >=1){
	$inhalt = $src->fetch_object();
  	$OnlyNumber = $inhalt->OnlyRare;
  	$Reserviert = $inhalt->Reserviert;
  	$Bezahlt = $inhalt->Bezahlt;
  	$Versendet = $inhalt->Versendet;
}else{
  	$OnlyNumber = NULL;
  	$Reserviert = 0;
  	$Bezahlt = 0;
  	$Versendet = 0;
}
  //$notification_text = "WHOOP WHOOP!";
  //$notification_class = "is-info is-light"; //is-success,is-warning
if($OnlyNumber != NULL){
	if($Reserviert != 0){
	$ausgabe = "is-warning is-light";
	}
	if($Bezahlt != 0){
	$ausgabe = "is-info is-light";
	}
	if($Versendet != 0){
	$ausgabe = "is-info is-light";
	}

}else{
$ausgabe = "is-success is-light";
}

return $ausgabe;
}

/***********************/

function Erstelle_Seed_Form($id){
$former = "";
$former.= "<form accept-charset='utf-8' class='box' action='/' method='post'>";
$former.=	"<label class='label required'>CRTSHT/".$id."</label>";
$former.=	"<div class='field is-grouped is-grouped-multiline'>";

$former.=	"<input class='input' name='frm' type='hidden' value='seed'>";
$former.=	"<input class='input' name='in' type='hidden' value='".$id."'>";
$former.=	"<div class='control has-icons-right'>";
$former.=	"<input class='input inputSeed1' id='seed1' name='in1' type='text' placeholder='1. seedword'>";
$former.= "<span class='icon is-small is-right'>";
$former.= "<i id='testseed1' class='fas fa-exclamation-triangle'></i>";
$former.= "</span>";
$former.=	"</div>";
//$former.=	"</div>";
//$former.=	"<div class='field'>";
$former.=	"<div class='control has-icons-right'>";
$former.=	"<input class='input inputSeed2' id='seed2' name='in2' type='text' placeholder='2. seedword'>";
$former.= "<span class='icon is-small is-right'>";
$former.= "<i id='testseed2' class='fas fa-exclamation-triangle'></i>";
$former.= "</span>";
$former.=	"</div>";
//$former.=	"</div>";
//$former.=	"<div class='field'>";
$former.=	"<div class='control has-icons-right'>";
$former.=	"<input class='input inputSeed3' id='seed3' name='in3' type='text' placeholder='3. seedword'>";
$former.= "<span class='icon is-small is-right'>";
$former.= "<i id='testseed3' class='fas fa-exclamation-triangle'></i>";
$former.= "</span>";
$former.=	"</div>";
//$former.=	"</div>";
//$former.=	"<div class='field'>";
$former.=	"<div class='control has-icons-right'>";
$former.=	"<input class='input inputSeed4' id='seed4' name='in4' type='text' placeholder='4. seedword'>";
$former.= "<span class='icon is-small is-right'>";
$former.= "<i id='testseed4' class='fas fa-exclamation-triangle'></i>";
$former.= "</span>";
$former.=	"</div>";
$former.=	"</div>";
$former.= "<button class='button is-primary' type='submit' id='sendseed' disabled='true'>OPEN</button>";
$former.=	"</form>";

return $former;
}
/***********************/

function Block_BlockchainInfo($address){
  if (empty($address)) return null;

  $url = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokennfttx&address=" . urlencode(trim($address)) .
         "&startblock=12548000&endblock=999999999&sort=asc&apikey=FYPV6M36Q44QQI2Y1GRUDT5VWZET1AK9U2";

  $response = GET_URL_ANTWORT($url);

  // If curl failed, GET_URL_ANTWORT returns "PROBLEM: ..."
  if (!is_string($response) || str_starts_with($response, "PROBLEM:")) {
    return "<div class='notification is-warning is-light'>Blockchain lookup failed: " . htmlspecialchars((string)$response) . "</div>";
  }

  $ok = json_decode($response, true);

  if (!is_array($ok)) {
    return "<div class='notification is-warning is-light'>Blockchain lookup returned invalid JSON.</div>";
  }

  // Etherscan uses status/message/result; status=1 => OK, otherwise result may be a string.
  if (!isset($ok["status"]) || $ok["status"] !== "1" || !isset($ok["result"]) || !is_array($ok["result"]) || empty($ok["result"])) {
    $msg = $ok["message"] ?? "NOTOK";
		$res = $ok["result"] ?? "";
		return "<div class='notification is-warning is-light'>
		Etherscan: ".htmlspecialchars($msg)." — ".htmlspecialchars(is_string($res)?$res:json_encode($res))."
		</div>";
  }

  $result = $ok["result"][0];  // now safe

  // --- compute gas cost safely ---
  $gasUsed  = isset($result["gasUsed"]) ? (float)$result["gasUsed"] : 0;
  $gasPrice = isset($result["gasPrice"]) ? (float)$result["gasPrice"] : 0; // wei
  $eth_amount = ($gasUsed * $gasPrice) / 1e18; // ETH
  $eth_price  = ETH_PRICE_TO_USD($eth_amount);

  $ausg  = "<div class='table-container'>";
  $ausg .= "<table class='table is-bordered is-striped is-narrow is-hoverable is-fullwidth'>";
  $ausg .= "<tr><td>block</td><td>".htmlspecialchars($result["blockNumber"] ?? "")."</td></tr>";

  $ts = (int)($result["timeStamp"] ?? 0);
	$time = $ts ? date("d M Y · H:i", $ts) : "-";
	$ausg.= "<tr><td>time</td><td>".$time."</td></tr>";
	
  $hash  = $result["hash"] ?? "";
  $ausg .= "<tr><td>hash</td><td><a target='_new' href='https://etherscan.io/tx/".htmlspecialchars($hash)."'>".htmlspecialchars($hash)."</a></td></tr>";
  $ausg .= "<tr><td>from</td><td>".htmlspecialchars($result["from"] ?? "")."</td></tr>";
  $ausg .= "<tr><td>to</td><td>".htmlspecialchars($result["to"] ?? "")."</td></tr>";
  $ausg .= "<tr><td>tokenID</td><td>".htmlspecialchars($result["tokenID"] ?? "")."</td></tr>";
  $ausg .= "<tr><td>name</td><td>".htmlspecialchars($result["tokenName"] ?? "")."</td></tr>";
  $ausg .= "<tr><td>confirmation</td><td>".htmlspecialchars($result["confirmations"] ?? "")."</td></tr>";

  // show gwei properly (optional)
  $gwei = $gasPrice / 1e9;
  $ausg .= "<tr><td>gasPrice</td><td>".htmlspecialchars(number_format($gwei, 2))." Gwei</td></tr>";
  $ausg .= "<tr><td>gasUsed</td><td>".htmlspecialchars((string)$gasUsed)."</td></tr>";
  $ausg .= "<tr><td>totalETH</td><td>".htmlspecialchars(number_format($eth_amount, 8))." (~$".htmlspecialchars($eth_price).")</td></tr>";
  $ausg .= "</table>";
  $ausg .= "</div>";

  return $ausg;
}

/***********************/

function Kopf(){
$ausg = "";
//$ausg.= "<section class='section'>";
/*
$ausg.= "<div class='media'>";
$ausg.= "<div class='media-left'>";
$ausg.= "<figure class='image is-48x48'>";
$ausg.= "<img class='is-square' src='/mor_site_pix/LogoQuadratCrtSht.jpg'>";
$ausg.= "</figure>";
$ausg.= "</div>";

$ausg.= "<div class='media-content'>";
$ausg.= "<h1 class='title is-size-1 is-size-3-mobile'>CRTSHT</h1>";
$ausg.= "<p class='subtitle is-size-4 is-size-6-mobile'><p>developed with <span class='icon'><i class='fas fa-heart'></i></span></p>";
$ausg.= "</div>";
$ausg.= "</div>";
*/
$ausg.= "<figure class='image is-5by1 mt-3'>";
$ausg.= "<div class='box' style='background-color: #ffffff30;'><a href='/'>";
$ausg.= "<img src='/mor_site_pix/MyOnlyRare_Head.png'>";
$ausg.= "</a></div>";
$ausg.= "</figure>";
//$ausg.= "</section>";

return $ausg;
}
/***********************/

function Section_CRTSH(){
$ausg = "";
$ausg.= "<section class='section'><div class='container'>";
$ausg.= "<div class='box top'>";
$ausg.= "<div class='columns is-widescreen'>";
$ausg.= "<div class='column'><a href='/about_crtsht'>";
$ausg.= "<figure class='image is-3by2'><img  src='/mor_site_pix/crtsht_daruma.png'></figure>";
$ausg.= "</a></div>";
$ausg.= "<div class='column is-one-third-widescreen is-half-tablet'>";
	$ausg.= "<div class='box is-fullheight'>";
$ausg.= "<div class='content has-text-centered'>";
$ausg.= "<h2>! CRTSHT IS MINTED !</h2>";
$ausg.= "<p>";
$ausg.= "The first batch of 128 genuine cryptoshits is minted, printed, tagged and ready to ship.</p>";
$ausg.= "<div class='buttons is-centered'><a href='/about_crtsht'><button class='button is-info'>ABOUT CRTSHT</button></a></div>";
$ausg.= "<div class='coin_box_id'><span class='tag is-info is-light'>Order your secret mooncake today!</span></div>";
$ausg.= "</div>";//content
	$ausg.= "</div>";//box rechts

	$ausg.= "<div class='buttons has-addons is-centered'>";
	$ausg.= "<button class='button is-primary'>available</button>";
	$ausg.= "<button class='button is-warning '>ordered</button>";
	$ausg.= "<button class='button is-link'>shipped</button>";
	$ausg.= "</div>";//buttons
$ausg.= "</div>";//column rechts
$ausg.= "</div>";//columns beide spalten
$ausg.= "</div></section>";
return $ausg;
}
/***********************/

function Section_About_crtsht(){
$ausg = "";
$ausg.= "<section class='section'>";
$ausg.= "<div class='box top'>";
$ausg.= "<div class='columns is-desktop'>";
$ausg.= "<div class='column'><figure class='image'>";
$ausg.= "<img src='/mor_site_pix/About_crtsht.jpg'>";
$ausg.= "</figure></div>";
$ausg.= "<div class='column is-one-third'>";
$ausg.= "<div class='content'>";
$ausg.= "<h2>CryptoShit available</h2>";
$ausg.= "<p>";
$ausg.= "The first batch of 128 genuine artworks is printed and ready to ship. Order yours today!&nbsp;";
$ausg.= "Each piece comes with a certificate in the form of a non-fungible token (NFT) minted by MyOnlyRare. Verify it yourself on the Ethereum Mainnet.";
$ausg.= "</p><p>";
$ausg.= "Behind each token there is a unique work of generated art by the grat shitmaker algorithm that ensures that no two images are alike.";
$ausg.= "</p>";
//$ausg.= "<div class='buttons is-right'><a href='/about_crtsht'<button class='button is-link is-light'>ABOUT CRTSHT</button></a></div>";
$ausg.= "</div>";
$ausg.= "</div>";
$ausg.= "</div>";
$ausg.= "</section>";
return $ausg;
}
/***********************/

function zeropad($num, $lim){
$inHex = dechex($num);
$anz = strlen($inHex);
$diff = 0;
$zero = null;
if($anz >= $lim){
$ausg = $inHex;
}else{
$diff = $lim - $anz;
for($x=0;$x<$diff;$x++){
$zero.= "0";
}
$ausg = $zero.$inHex;
}
return $ausg;
}
/***********************/

function Vorschau($von,$bis){
$ausg ="";
for($ID = $bis; $ID >= $von; $ID--){

  $filename = "CoinThumb_1-128/".$ID."_thumb.jpg";
  if (file_exists($filename)) {
$ausg.= "<div class='column is-one-fifth-desktop is-one-quarter-tablet is-one-third-mobile'>";

$ausg.= "<a href='/crtsht/".$ID."'>";
$ausg.= "<div class='box'>";
$ausg.= "<div class='coin_box_num'>".$ID."</div>";
$ausg.= "<figure class='image is-square'>";
$ausg.= "<img src='/".$filename."'>";
$ausg.= "</figure>";
$ausg.= "<div class='coin_box_id'><span class='tag is-info is-light'>0x".zeropad($ID,4)."</span></div>";
$ausg.= "</div>";
$ausg.= "</a>";

$ausg.= "</div>";
}else{
  $ausg.="SHIT";
}

}
return $ausg;
}
/***********************/

function GET_MY_DATA($field,$suchID){
global $mysqli;
$ausgabe = "";
$query = sprintf("SELECT `".$field."` FROM `ShitID` WHERE `ID` = '%s'", mysqli_real_escape_string($mysqli, $suchID));
$src = mysqli_query($mysqli,$query);
	if($src->num_rows >=1){
	$inhalt = $src->fetch_object();
  $ausgabe = $inhalt->$field;
}
return $ausgabe;
}
/***********************/

function Shitter_ID($ID){
$filename = "JSON_1-128/".$ID.".json";
if (file_exists($filename)) {
  $string = file_get_contents($filename);
  $json_a = json_decode($string, true);
  //echo $json_a['John'][status];
  //echo $json_a['Jennifer'][status];
  $picture_ipfs = substr($json_a['image'],7);
  $picture_path = "https://ipfs.io/ipfs/".$picture_ipfs;

  if($ID >= 1 && $ID <= 128){
    $ETH_ADDR = GET_MY_DATA("ETH_Adr",$ID);
    $IPFS_JSON = GET_MY_DATA("IPFS_JSON",$ID);
    $IPFS_COIN = GET_MY_DATA("IPFS_COIN",$ID);
    $mintIDs = $json_a['attributes'][1]['value'].".".$json_a['attributes'][2]['value'].".".$json_a['attributes'][3]['value'];
    $json_path = "<a href ='/JSON_1-128/".$ID.".json'>/JSON_1-128/".$ID.".json</a>";
    $token_path = "<a target='_new' href ='https://etherscan.io/token/0x4ca92d5e15263b7b1c1afb6bda9caf70d98fd038?a=".$ID."#inventory'>".$mintIDs."</a>";
    $owner_path = "<a target='_new' href ='https://etherscan.io/token/0x4ca92d5e15263b7b1c1afb6bda9caf70d98fd038?a=".$ETH_ADDR."'>".$ETH_ADDR."</a>";
    $opensea_path = "<a target='_new' href ='https://opensea.io/assets/0x4ca92d5e15263b7b1c1afb6bda9caf70d98fd038/".$ID."'>NFT[".$ID."]</a>";
    $token_token = "<a target='_new' href ='https://etherscan.io/token/0x4ca92d5e15263b7b1c1afb6bda9caf70d98fd038'>Token MyOnlyRare_0_128</a>";
    $token_title = $mintIDs;
  }else{
    $json_path = "UUPS";
    $token_path = NULL;
    $token_title = $ID;
  }

  if($ID<=32){
    $Sold_Status = "<span class='tag is-warning'>SOLD</span>";
  }else{
    $Sold_Status = "<span class='tag is-success'>AVAILABLE</span>";
  }



	$ausgabe["FOUND"] = "ID";
	$ausgabe["STATUS"] = $token_token;
	$ausgabe["ID"] = $json_a['name'];
    $ausgabe["JSON"] = $json_path;
    $ausgabe["TITLE"] = $token_title;
	$ausgabe["PROOF"] = $token_path;
	$ausgabe["ADDR-ETH"] = $owner_path;
	$ausgabe["ADDR-ETH_RAW"] = $ETH_ADDR;
	$ausgabe["PRINT-HASH"] = $json_a['attributes'][4]['value'];
	$ausgabe["MINT"] = $mintIDs;
	$ausgabe["WHIRLLOGIN"] = "";
	$ausgabe["BILD"] = $picture_path;
    $ausgabe["OPENSEA"] = $opensea_path;
    $ausgabe["POO"] = "";
    $ausgabe["IPFS_JSON"] = $IPFS_JSON;
    $ausgabe["IPFS_COIN"] = $IPFS_COIN;
    $ausgabe["SOLD"] = $Sold_Status;
	}else{
	$ausgabe["FOUND"] = FALSE;
	$ausgabe["STATUS"] = "UUPS 404 WE LOST CONTACT TO ".$ID."...";
	$ausgabe["ID"] = "";
    $ausgabe["JSON"] = "";
    $ausgabe["TITLE"] = "";
	$ausgabe["PROOF"] = "";
	$ausgabe["ADDR-ETH"] = "";
	$ausgabe["ADDR-ETH_RAW"] = "";
	$ausgabe["PRINT-HASH"] = "";
	$ausgabe["MINT"] = "";
	$ausgabe["WHIRLLOGIN"] = "";
	$ausgabe["BILD"] = "mor_site_pix/cryptoshit0000.jpg";
    $ausgabe["OPENSEA"] = "";
    $ausgabe["POO"] = "";
    $ausgabe["IPFS_JSON"] = "";
    $ausgabe["IPFS_JSON"] = "";
    $ausgabe["SOLD"] = "";
	}
return $ausgabe;
}

/***********************/

function Shitter_code($ID){
global $mysqli;

$shittyID = 00000000;
if(isset($ID)){
  if(empty($ID)){
    $shittyID = 00000000;
  }else{
    $shittyID = $ID;
  }
}
$suchID = substr(str_pad($shittyID,  8, "x"),0,8);
$ausgabe = array();

$query = sprintf("SELECT * FROM `ShitID` WHERE `LoginWhirlpool` = '%s'", mysqli_real_escape_string($mysqli, $suchID));
$src = mysqli_query($mysqli,$query);
	if($src->num_rows >=1){
	$inhalt = $src->fetch_object();
	$ID			= $inhalt->ID;
	$ShitID		= $inhalt->ShitID;
	$Bildpfad	= $inhalt->Bildpfad;
	$BTC_Ad		= $inhalt->BTC_Adr;
	$ETH_Ad		= $inhalt->ETH_Adr;
	$Hash_Print	= $inhalt->Hasher_Druck;
	$Whirlpool	= $inhalt->Whirlpool;
	$WhirlpoolL	= $inhalt->LoginWhirlpool;
  $IPFS_PIX 	= $inhalt->IPFS_COIN;
  $IPFS_JSON 	= $inhalt->IPFS_JSON;
  $POO = substr(hash("whirlpool",$WhirlpoolL.".never.fiber.inside.level"),0,17);

	$ausgabe["FOUND"] = "CODE";
	$ausgabe["STATUS"] = "Congratulations ! Lucky you ! You have just unlocked your secret mooncake.";
  $ausgabe["LNR"] = $ID;
	$ausgabe["ID"] = $ShitID;
	$ausgabe["ADDR-BTC"] = $BTC_Ad;
	$ausgabe["ADDR-ETH"] = $ETH_Ad;
	$ausgabe["PRINT-HASH"] = $Hash_Print;
	$ausgabe["WHIRLPOOL"] = $Whirlpool;
	$ausgabe["WHIRLLOGIN"] = $WhirlpoolL;
	$ausgabe["BILD"] = $Bildpfad;
  $ausgabe["POO"] = $POO;
	}else{
	$ausgabe["FOUND"] = FALSE;
	$ausgabe["STATUS"] = "UUPS 404 WE LOST CONTACT TO ".$suchID."...";
  $ausgabe["LNR"] = "";
	$ausgabe["ID"] = "";
	$ausgabe["ADDR-BTC"] = "";
	$ausgabe["ADDR-ETH"] = "";
	$ausgabe["PRINT-HASH"] = "";
	$ausgabe["WHIRLPOOL"] = "";
	$ausgabe["WHIRLLOGIN"] = "";
	$ausgabe["BILD"] = "mor_site_pix/cryptoshit0000.jpg";
  $ausgabe["POO"] = "";
	}


return $ausgabe;
$mysqli_close();
}

/***********************/

function uups($deep){
$ausg = "";
$ausg.= "<section class='section'>";
$ausg.= "<div class='container is-fluid'>";
$ausg.= "<div class='notification is-danger'>";
$ausg.= "UUPS 404 WE LOST CONTACT TO ".$deep."...";
$ausg.= "</div>";
$ausg.= "</div>";
$ausg.= "</section>";
return $ausg;
}
/***********************/

function notification($class,$message){
$ausg = "";
$ausg.= "<div class='notification ".$class."'><button class='delete'></button>";
$ausg.= $message;
$ausg.= "</div>";
return $ausg;
}
/***********************/

function Linker($in){
global $mysqli_USER;
$input = mysqli_real_escape_string($mysqli_USER,$in);
$input_len = strlen($input);
if($input_len >= 9){$input = substr($input,0,8); }else{$input = $input;}
if(substr($input,0,2)== "0x"){
	$deep = hexdec(substr($input,2,6));
}else{
	$deep = $input;
}
  if(is_numeric($deep)){
    $Truffle = Shitter_ID($deep);
	}else{
    $Truffle = Shitter_code($deep);
  }
$ausg = "";

switch ($Truffle["FOUND"]) {
  case 'CODE':
  $ausg.= "<section class='section'>";

  $ausg.= "<div class='content'>";
  $ausg.= "<h1>/CRTSHT/".$Truffle["ID"]."</h1>";
  $ausg.= "</div>";

  $ausg.= "<div class='card'>";
  $ausg.= "<div class='card-image'>";
  $ausg.= "<figure class='image is-square'>";
  $ausg.= "<p class='card-text' id='bullshit'></p>";
  $ausg.= "<img id='bubble' src='/mor_site_pix/sprechblase.png' style='z-index:2; display:none;' alt='bullshit bubble'>";
  $ausg.= "<img src='/".$Truffle["BILD"]."'>";
  $ausg.= "</figure>";
  $ausg.= "</div>";
  $ausg.= "<div class='card-content'>";
  $ausg.= "<div class='media'><div class='media-left'>";
  $ausg.= "<figure class='image is-128x128'>";
  $ausg.= "<img class='is-square' src='/coin/numcoin-with/".$Truffle["LNR"]."-".$Truffle["POO"].".jpg'>";
  $ausg.= "</figure></div>";
  $ausg.= "<div class='media-content'>";
  $ausg.= "<p class='title is-4'>金のうんこ</p>";
  $ausg.= "<p class='subtitle is-6'>«".$Truffle["STATUS"]."»</p>";
  $ausg.= "</div>";
  $ausg.= "</div>";

  $ausg.= "<div class='table-container'>";
  $ausg.= "<table class='table is-bordered is-striped is-narrow is-hoverable is-fullwidth'>";
  $ausg.= "<tr><th>Meta</th><th>Data</th></tr>";
  $ausg.= "<tr><td>Minted</td><td>2021</td></tr>";
  $ausg.= "<tr><td>[ETH] Public<br>Address</td><td><a href='https://etherscan.io/address/".$Truffle["ADDR-ETH"]."'>Etherscan</a><br>".$Truffle["ADDR-ETH"]."</td></tr>";
  $ausg.= "<tr><td>[BTC] Public<br>Address</td><td><a href='https://www.blockchain.com/search?search=".$Truffle["ADDR-BTC"]."'>Blockexplorer</a><br>".$Truffle["ADDR-BTC"]."</td></tr>";
  $ausg.= "<tr><td>Print Hash</td><td>".$Truffle["PRINT-HASH"]."</td></tr>";
  $ausg.= "</table>";
  $ausg.= "</div>";


  $ausg.= "<footer class='card-footer'>";
  $ausg.= "<a class='card-footer-item'>";
  $ausg.= "<div class='tags has-addons is-pulled-right'>";
  $ausg.= "<span class='tag is-dark'>".$Truffle["ID"]."</span>";
  $ausg.= "<span class='tag is-warning'>NFT-PROOF</span>";
  $ausg.= "</div>";
  $ausg.= "</a>";
  $ausg.= "</footer>";
  $ausg.= "</div>";

  $ausg.= "</section>";

    break;
  case 'ID':
  $ausg.= "<section class='section'>";
  $ausg.= "";//notification($Truffle["NotClass"],$Truffle["NotText"]);

  $ausg.= "<div class='content'>";
  $ausg.= "<h2>".$Truffle["TITLE"]."</h2>";
  $ausg.= "</div>";

  $ausg.= "<div class='box'>";
  //$ausg.= "<div class='card-image'>";
  $ausg.= "<figure class='image is-square'>";
  $ausg.= "<img src='".$Truffle["BILD"]."'>";
  $ausg.= "</figure>";
  //$ausg.= "</div>";
  //$ausg.= "<div class='card-content'>";
  $ausg.= "<div class='media-content'>";
  $ausg.= "<p class='subtitle is-6'>".$Truffle["JSON"]."</p>";
  $ausg.= "</div>";

  $ausg.= "<div class='table-container'>";
  $ausg.= "<table class='table is-bordered is-striped is-narrow is-hoverable is-fullwidth'>";
  $ausg.= "<tr><th>Meta</th><th>Data</th></tr>";
  $ausg.= "<tr><td>TOKEN</td><td>".$Truffle["PROOF"]."</td></tr>";
  $ausg.= "<tr><td>Owner<td>".$Truffle["ADDR-ETH"]."</td></tr>";
  $ausg.= "<tr><td>Market<td>".$Truffle["OPENSEA"]."</td></tr>";
  $ausg.= "<tr><td>IPFS</td><td><a href='https://ipfs.io/ipfs/".$Truffle["IPFS_JSON"]."'>[JSON]</a>&nbsp;|&nbsp;";
  $ausg.= "<a href='https://ipfs.io/ipfs/".$Truffle["IPFS_COIN"]."'>[PICTURE]</a></td></tr>";
  $ausg.= "<tr><td>POP*</td><td>".$Truffle["PRINT-HASH"]."</td></tr>";
  $ausg.= "</table>";
  $ausg.= "</div>";

  $ausg.= Block_BlockchainInfo($Truffle["ADDR-ETH_RAW"]);

  $ausg.= "<div class='media-content'>";
  $ausg.= "<p class='subtitle is-6'>".$Truffle["STATUS"]."</p>";
  $ausg.= "</div>";

  $ausg.= "<footer class='card-footer'>";
  $ausg.= "<div class='card-footer-item'>";
  $ausg.= "<div class='tags has-addons is-pulled-right'>";
  $ausg.= "<span class='tag is-dark'>".$Truffle["ID"]."</span>";
  $ausg.= $Truffle["SOLD"];
  $ausg.= "</div>";
  $ausg.= "</div>";
  $ausg.= "</footer>";
  $ausg.= "</div>";

	$ausg.= Erstelle_Seed_Form($Truffle["ID"]);

  $ausg.= "</section>";

    break;

  default:
  $ausg.= "<section class='section'>";
  $ausg.= "<div class='container is-fluid'>";
  $ausg.= "<div class='notification is-danger'>";
  $ausg.= $Truffle["STATUS"];
  $ausg.= "</div>";
  $ausg.= "</div>";
  $ausg.= "</section>";
    break;
}


return $ausg;
}
/***********************/
$ausgabe = "";
$ausgabe_vorschau = "";
$FormIn_frm = "";
$FormIn_in0 = "";
$FormIn_in1 = "";
$FormIn_in2 = "";
$FormIn_in3 = "";
$FormIn_in4 = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$FormIn_frm = $_POST["frm"];
	if(!empty($FormIn_frm)){
		switch ($FormIn_frm) {
			case 'seed':
			$FormIn_in0 = $_POST["in"];
			$FormIn_in1 = $_POST["in1"];
			$FormIn_in2 = $_POST["in2"];
			$FormIn_in3 = $_POST["in3"];
			$FormIn_in4 = $_POST["in4"];
			$firstwords = strtolower($FormIn_in1.".".$FormIn_in2.".".$FormIn_in3.".".$FormIn_in4);
			$Hash_BildHex_und_firstwords = substr(hash("whirlpool",$FormIn_in0.".".$firstwords),0,8);
			$path = "crtsht/".$Hash_BildHex_und_firstwords;
				break;

			default:
				$path = "crtsht/0x0000";
				break;
		}
	}
}else{
$path = ltrim($_SERVER['REQUEST_URI'], '/');    // Trim leading slash(es) ergibt -> /crtsht/128
}//else request not POST

$elements = explode('/', $path);                // Split path on slashes
if(empty($elements[0])) {                       // No path elements means home
   $ausgabe = Kopf();
	 $ausgabe.= Section_CRTSH();
   $ausgabe_vorschau = Vorschau(1,128);
}else{
 $input = $elements[0];
 switch($input){
    case stristr($input,'CRTSHT'):
		case stristr($input,'crtsht'):
    if(empty($elements[1])){$elements[1] = "";}
        $ausgabe = Kopf();
        $ausgabe.= Linker($elements[1]); // passes rest of parameters to internal function
        $ausgabe_vorschau = Vorschau(1,128);
        break;
		case stristr($input,'about_crtsht'):
				$ausgabe = Kopf();
				$ausgabe.= section_About_crtsht(); //aboutcrtsh
				$ausgabe_vorschau = Vorschau(1,128);
				break;
    default:
    	$ausgabe = Kopf();
      $ausgabe_vorschau = Vorschau(1,128);
        if(empty($elements[1])){
        $ausgabe.= uups("brrrz");
        }else{
        $ausgabe.= uups($elements[0]."/".$elements[1]);
        }
        break;
}
}//else !empty elements


?>

<!DOCTYPE html>
<html class="My_flow_background">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MYONLYRARE.COM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.1/css/bulma.min.css">
		<script src="https://kit.fontawesome.com/6ce70e804d.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,500;0,700;1,300;1,500;1,700&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link  href="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.js"></script>
    <link rel="apple-touch-icon" sizes="180x180" href="/fav/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/fav/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/fav/favicon-16x16.png">
<link rel="manifest" href="/fav/site.webmanifest">
<style>
body{
  font-family: 'Roboto', sans-serif;
}
.subtitle{
  font-weight: lighter;
}

figure.farbig{
	background-color: pink;
	background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
	background-size: 400% 400%;
	animation: gradient 15s ease infinite;
}
@keyframes gradient {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}

.column{
padding:0.5rem;
}

.section{
padding: 2rem 0.5rem;
}

.box{
padding: 0.5rem;
position:relative;
}

.box.coins{
padding: 0.4rem;
box-shadow: none;
background-color: #ffffff00;
}

.box.top{
padding: 1.15rem;
position:relative;
}

.coin_box_id{
display: grid;
}
.coin_box_num{
position: absolute;
top:0px;
left:2px;
font-size:xx-small;
z-index:1;
color:lightgrey;
}

.main-loading {
position: fixed;
top: 0px;
left: 50%;
transform: translate(-50%,-50%);
-webkit-transform: translate(-50%,-50%);
-o-transform: translate(-50%,-50%);
-moz-transform: translate(-50%,-50%);
-ms-transform: translate(-50%,-50%);
background-color: #fff;
border-radius: 5px;
-webkit-border-radius: 5px;
-moz-border-radius: 5px;
-o-border-radius: 5px;
-ms-border-radius: 5px;
background-image: url('/mor_site_pix/spinner.svg');
background-size: 100%;
width: 80px;
height: 80px;
box-shadow: 0 0 40px rgba(0,0,0,0.05);
-webkit-box-shadow: 0 0 40px rgba(0,0,0,0.05);
-moz-box-shadow: 0 0 40px rgba(0,0,0,0.05);
-ms-box-shadow: 0 0 40px rgba(0,0,0,0.05);
-o-box-shadow: 0 0 40px rgba(0,0,0,0.05);
z-index: 999999;
}
#bullshit{
position: absolute;
padding: 0 0.2em;
top:0px;
font-weight: bold;
z-index: 9000;
font-size:4.0em;
}


.My_flow_background {
    background: linear-gradient(137deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    background-size: 800% 800%;

    -webkit-animation: OnlyRareFlow 52s ease infinite;
    -moz-animation: OnlyRareFlow 52s ease infinite;
    animation: OnlyRareFlow 52s ease infinite;
}

@-webkit-keyframes OnlyRareFlow {
    0%{background-position:0% 87%}
    50%{background-position:100% 14%}
    100%{background-position:0% 87%}
}
@-moz-keyframes OnlyRareFlow {
    0%{background-position:0% 87%}
    50%{background-position:100% 14%}
    100%{background-position:0% 87%}
}
@keyframes OnlyRareFlow {
    0%{background-position:0% 87%}
    50%{background-position:100% 14%}
    100%{background-position:0% 87%}
}
</style>

  </head>

<body>
<div class="container is-max-desktop">

<?php echo $ausgabe; ?>

<section class="section pt-0">
<div class='box coins'>
<!-- // -->
<div class="columns is-multiline is-mobile">
<!-- // -->
<?php echo $ausgabe_vorschau; ?>
<!-- // -->
</div>
<!-- // -->
</div>

<div class="box" style="background-color: #ffffff30;">
  <div class="tags are-medium has-addons">
  <span class="tag is-warning "><i class="fab fa-bitcoin"></i></span>
  <span class="tag is-success">cryptos accepted here</span>
  </div>
	<div class="content has-text-right">
		<p>powered by <strong><a class="button is-small is-link is-light" href='http://www.ibulla.com'>iBulla.com</a></strong> made with <span class="icon">
  <i class="fas fa-heart"></i></span></p>
	</div>
</div>
</section>

</div>

<script type="text/javascript" src="/js/seed_validate.js?v5" async defer></script>

</body>
</html>

<script>

document.addEventListener('DOMContentLoaded', () => {
  (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
    const $notification = $delete.parentNode;

    $delete.addEventListener('click', () => {
      $notification.parentNode.removeChild($notification);
    });
  });
});


$(document).ready(function() {


var $bubble = $('#bubble');
if (!$bubble.is(':visible')) {
    $bubble.fadeIn(6500);
    $.ajax({
      url: "/bullshiter.php",
      dataType: 'json',
      success: function(data){
         $('#bullshit').html(data.bullshit).hide();
         testfontsize();
           $('#bullshit').fadeIn(8500);
      }
  });

}

/*----*/

function testfontsize(){
var $quote = $("#bullshit");
var $numWords = $quote.text().length;
var screen = $(window);

if (($numWords >= 1) && ($numWords < 35)) {
	if (screen.width() < 400) {
	$quote.css("font-size", "2.0em");
	}else{
    $quote.css("font-size", "4.0em");
    }
}
else if (($numWords >= 35) && ($numWords < 60)) {
	if (screen.width() < 400) {
	$quote.css("font-size", "1.5em");
	}else{
    $quote.css("font-size", "3.0em");
    }
}
else if (($numWords >= 60) && ($numWords < 90)) {
	if (screen.width() < 400) {
	$quote.css("font-size", "1.2em");
	}else{
    $quote.css("font-size", "2.5em");
    }
}
else if (($numWords >= 90) && ($numWords < 128)) {
	if (screen.width() < 400) {
	$quote.css("font-size", "1.0em");
	}else{
    $quote.css("font-size", "1.25em");
    }
}
else {
	if (screen.width() < 400) {
	$quote.css("font-size", "0.8em");
	}else{
    $quote.css("font-size", "0.8em");
    }
}
};

/*
         $('a.bit').on("click",function() {
          var klick = $(this).attr("id");
          $('#card-'+ klick).toggleClass("is-hidden");
          return false;
         });
*/
});

</script>
