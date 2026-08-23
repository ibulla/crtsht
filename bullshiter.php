<?php
//require_once('inc/verbindung.php');
/*--------------*/
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
/*--------------*/
function GasToTime($gas){
	$klein = ($gas/10000000000000);
	$kleiner = round(($klein/5),0, PHP_ROUND_HALF_UP);
	return $kleiner;
}
/*--------------*/

function GasBrenntNoch(){
	global $conn;
	$block_bis_ausgabe = mysqli_query($conn,"SELECT * FROM `BURNER` LIMIT 1");
	$DB_Block_BURNER 	 = mysqli_fetch_object($block_bis_ausgabe);
	$DB_bis_block			 = $DB_Block_BURNER->BLOCK_END;
	return $DB_bis_block;
}
/*--------------*/

if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	/*LAST BLOCK ID
	$latestBlockUrl = "https://api.etherscan.io/api?module=proxy&action=eth_blockNumber&apikey=MADXDXQ79UVPT622YRQZP3AHZZBX5I43M8";
	$re_block_last = GET_URL_ANTWORT($latestBlockUrl);
	$last_block = json_decode($re_block_last);
	$show_last_block_id  = $last_block->{"result"};
	$last_block_id = hexdec($show_last_block_id);
	/*LAST BLOCK ID FERTIG*/

	/*KONTOSTAND TOTAL
  $url  = "https://api.etherscan.io/api?module=account&action=balance&address=0x691712151B62BfF3564fC21EA1922336D45e4Aeb&tag=latest&apikey=MADXDXQ79UVPT622YRQZP3AHZZBX5I43M8";
	$response = GET_URL_ANTWORT($url);
  $ok = json_decode($response);
  $result  = $ok->{"result"};
  $kontostand = number_format(($result/1000000000000000000),10);
	/*KONTOSTAND TOTAL FERTIG*/


	$bullshit = ["You need that piece of shit for real",
							 "the clever shit's behind it",
							 "it's an instance of true authenticity",
							 "this is the real shit, be ready for the next generation",
							 "each work is uniquely created by the great Shitmaker-Algorithm himself",
							 "no shit is like any other",
							 "this is the real shit",
							 "cause it's more than just a print",
							 "printed once and certified by your personal token",
	             "becuase it's pretty cool to know that your shit is on the blockchain and you own it",
							 "this shit is big and has even sprinkles on top",
							 "Shit Yeah!",
						   "Congratulations, you've found the truffle!"];
	
	$kin_no_unko = ["No matter how many coins there are, one satoshi will rule them all.",
				  "Keep on tracking, keep on stacking",
				  "There is a shitcoin around every corner, so never turn off your track",
				  "Do not run fast, run with one eye on the ground and you will find your truffle",
				  "May the force be with you, may your contract never run out of gas.",
				  "Never invest in a shitcoin you not understand, unless it's your own.",
				  "Keep it decentralized and farming.",
				  "Strong hands and diamond mind.",
				  "Just hodl.",
				  "My name is Coin - Bitcoin.",
				  "Let's eat some shitcoins for breakfast today.",
				  "Choose the ingredients carefully.",
				  "Your fungibility stew will taste delicious.",
				  "One coin to rule them all.",
				  "One coin to find them all.",
				  "One coin to bring them all and in the blockchain bind them.",
				  "Cryptography is the secret ingredient.",
				  "One block after one block.",
				  "Never go full retard.",
				  "Go full stake today.",
				  "Take profit - NOW!",
				  "Data-data-metadata, keep it secret.",
				  "Always make your own research.",
				  "Never invest on the advice of a martian.",
				  "Never trust the market cap, shitcoins are good in mimikri.",
				  "There is lot of FUD out there.",
				  "Make your own rainbow chart today.",
				  "In code we trust.",
				  "Research today about Peer-to-peer networks.",
				  "Continue your education.",
				  "Educate yourself further today about trustless systems.",
				  "Trust the trustless consensus."];
	
	//$bullshitwahl  = rand(0,(count($bullshit)-1)); // $bullshit[$bullshitwahl],
	$bullshitwahl  = rand(0,(count($kin_no_unko)-1));

    			echo json_encode(["bullshit" => $kin_no_unko[$bullshitwahl],
														 "on" => true]);

}else{
    echo "<h1>Access forbidden, kids!</h1>";
}
