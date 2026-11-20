<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/stripe.php';
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {http_response_code(405);exit('Method not allowed.');}
if (!crt_stripe_ready()) {http_response_code(503);exit('Stripe checkout is not enabled.');}
$code=strtoupper(trim((string)($_POST['reservation']??'')));
if (!preg_match('/^W-[A-F0-9]{16}$/',$code)) {http_response_code(400);exit('Invalid payment reference.');}
$db=crt_db();
if(!$db){http_response_code(503);exit('Database unavailable.');}
try {
    $db->begin_transaction();
    // Read work ID without locking, then lock the work before its offer to match admin order.
    $stmt=$db->prepare('SELECT SelectedWorkID FROM CRTSHT_Selected_Offers WHERE ReservationCode=?');
    if(!$stmt)throw new RuntimeException('Offer lookup failed.');
    $stmt->bind_param('s',$code);$stmt->execute();$link=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$link)throw new RuntimeException('Payment link not found.');
    $workId=(int)$link['SelectedWorkID'];
    $stmt=$db->prepare('SELECT ID,CRTSHT_ID,SaleStatus,WinnerOfferID,(ReservationExpiresAt>NOW()) AS IsCurrent FROM CRTSHT_Selected_Works WHERE ID=? FOR UPDATE');
    if(!$stmt)throw new RuntimeException('Work lookup failed.');
    $stmt->bind_param('i',$workId);$stmt->execute();$work=$stmt->get_result()->fetch_assoc();$stmt->close();
    $stmt=$db->prepare('SELECT ID,ReservationCode,Email,OfferedPriceCHF,StripeCheckoutSessionID,Status FROM CRTSHT_Selected_Offers WHERE SelectedWorkID=? AND ReservationCode=? FOR UPDATE');
    if(!$stmt)throw new RuntimeException('Reservation lookup failed.');
    $stmt->bind_param('is',$workId,$code);$stmt->execute();$offer=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$work||!$offer||$work['SaleStatus']!=='reserved'||(int)$work['WinnerOfferID']!==(int)$offer['ID']
        ||$offer['Status']!=='accepted'||(int)$work['IsCurrent']!==1)throw new RuntimeException('This reservation is not open for payment.');
    $amount=crt_stripe_chf_cents((float)$offer['OfferedPriceCHF']);
    if($amount<50)throw new RuntimeException('Invalid payment amount.');
    $existing=(string)($offer['StripeCheckoutSessionID']??'');
    if($existing!==''){
        if(!preg_match('/^cs_[A-Za-z0-9_]+$/',$existing))throw new RuntimeException('Invalid stored session ID.');
        $old=crt_stripe_request('GET','/v1/checkout/sessions/'.rawurlencode($existing));
        if(($old['status']??'')==='open'&&!empty($old['url'])){
            $db->commit();$db->close();
            header('Location: '.(string)$old['url'],true,303);exit;
        }
        if(($old['status']??'')!=='expired')throw new RuntimeException('Previous payment is being verified. Please try again after confirmation.');
    }
    $base=crt_stripe_base_url();
    $params=[
        'mode'=>'payment',
        'client_reference_id'=>$code,
        'customer_email'=>(string)$offer['Email'],
        'success_url'=>$base.'/stripe/selected-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'=>$base.'/selected-payment/'.rawurlencode($code),
        'line_items'=>[[
            'quantity'=>1,
            'price_data'=>[
                'currency'=>'chf','unit_amount'=>$amount,
                'product_data'=>[
                    'name'=>'CRTSHT / SELECTED WORK #'.(int)$work['CRTSHT_ID'],
                    'description'=>'One specifically selected CRTSHT artwork. Reference '.$code,
                ],
            ],
        ]],
        'metadata'=>[
            'crtsht_mode'=>'selected_work',
            'crtsht_selected_offer_id'=>(string)$offer['ID'],
            'crtsht_selected_work_id'=>(string)$workId,
            'crtsht_selected_code'=>$code,
        ],
        'invoice_creation'=>['enabled'=>'true'],
    ];
    $idem='crtsht-selected-'.(int)$offer['ID'].'-'.substr(hash('sha256',$existing),0,24);
    $session=crt_stripe_request('POST','/v1/checkout/sessions',$params,$idem);
    $sid=(string)($session['id']??'');$url=(string)($session['url']??'');
    if(!preg_match('/^cs_[A-Za-z0-9_]+$/',$sid)||!str_starts_with($url,'https://checkout.stripe.com/'))
        throw new RuntimeException('Stripe returned an invalid checkout session.');
    $status='open';
    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET StripeCheckoutSessionID=?,StripePaymentStatus=? WHERE ID=? AND Status='accepted'");
    if(!$stmt)throw new RuntimeException('Could not attach checkout session.');
    $offerId=(int)$offer['ID'];$stmt->bind_param('ssi',$sid,$status,$offerId);
    if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Could not store checkout session.');
    $stmt->close();
    $db->commit();$db->close();
    header('Location: '.$url,true,303);exit;
} catch(Throwable $e) {
    try{$db->rollback();}catch(Throwable $ignore){}
    $db->close();
    error_log('CRTSHT selected checkout error: '.$e->getMessage());
    http_response_code(409);
    $detail=!crt_stripe_is_live()?'<p>Sandbox detail: '.crt_e($e->getMessage()).'</p>':'';
    echo '<!doctype html><meta charset="utf-8"><title>CRTSHT / PAYMENT</title><p>Payment could not be started or is no longer available.</p>'.$detail
        .'<p><a href="/selected-payment/'.rawurlencode($code).'">Return to payment options</a></p>';
}
