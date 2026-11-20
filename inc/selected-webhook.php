<?php
declare(strict_types=1);
require_once __DIR__ . '/selected-mail.php';

/**
 * Called only AFTER Stripe signature verification in stripe/webhook.php.
 * A processed event is committed atomically with the resulting sale status.
 */
function crt_selected_handle_stripe_event(mysqli $db,string $eventId,string $type,array $session): array {
    $meta=is_array($session['metadata']??null)?$session['metadata']:[];
    $workId=filter_var($meta['crtsht_selected_work_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
    $offerId=filter_var($meta['crtsht_selected_offer_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
    $code=strtoupper(trim((string)($meta['crtsht_selected_code']??'')));
    $sessionId=(string)($session['id']??'');
    if(!$workId||!$offerId||!preg_match('/^W-[A-F0-9]{16}$/',$code)
        ||!preg_match('/^cs_[A-Za-z0-9_]+$/',$sessionId))throw new RuntimeException('Selected payment metadata invalid.');
    $notify=null;$wasDuplicate=false;
    $db->begin_transaction();
    try {
        $stmt=$db->prepare('INSERT IGNORE INTO CRTSHT_Stripe_Events (StripeEventID,EventType,ProcessedAt) VALUES (?,?,NOW())');
        if(!$stmt)throw new RuntimeException('Stripe event log unavailable.');
        $stmt->bind_param('ss',$eventId,$type);
        if(!$stmt->execute())throw new RuntimeException('Event log failed.');
        $wasDuplicate=$stmt->affected_rows===0;$stmt->close();
        if(!$wasDuplicate) {
            // Lock order matches admin: work first, then offer.
            $stmt=$db->prepare('SELECT ID,CRTSHT_ID,SaleStatus,WinnerOfferID FROM CRTSHT_Selected_Works WHERE ID=? FOR UPDATE');
            if(!$stmt)throw new RuntimeException('Work lookup failed.');
            $stmt->bind_param('i',$workId);$stmt->execute();$work=$stmt->get_result()->fetch_assoc();$stmt->close();
            $stmt=$db->prepare('SELECT * FROM CRTSHT_Selected_Offers WHERE ID=? AND SelectedWorkID=? AND ReservationCode=? FOR UPDATE');
            if(!$stmt)throw new RuntimeException('Offer lookup failed.');
            $stmt->bind_param('iis',$offerId,$workId,$code);$stmt->execute();$offer=$stmt->get_result()->fetch_assoc();$stmt->close();
            if(!$work||!$offer||(int)$work['WinnerOfferID']!==$offerId
                ||(string)$offer['StripeCheckoutSessionID']!==$sessionId
                ||(string)($session['client_reference_id']??'')!==$code)
                throw new RuntimeException('Selected-work checkout mismatch.');
            $isPaid=$type==='checkout.session.async_payment_succeeded'
                ||($type==='checkout.session.completed'&&($session['payment_status']??'')==='paid');
            if($isPaid) {
                $currency=strtolower((string)($session['currency']??''));
                $amount=isset($session['amount_total'])?(int)$session['amount_total']:-1;
                $expected=crt_stripe_chf_cents((float)$offer['OfferedPriceCHF']);
                if($currency!=='chf'||$amount!==$expected||$expected<50)
                    throw new RuntimeException('Selected-work payment amount mismatch.');
                if($work['SaleStatus']==='sold'&&$offer['PaidAt']!==null) {
                    // Replayed success under a different Stripe event ID; never send a duplicate mail.
                } elseif($work['SaleStatus']==='reserved'&&$offer['Status']==='accepted') {
                    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET PaidAt=NOW(),StripePaymentStatus='paid' WHERE ID=? AND Status='accepted' AND PaidAt IS NULL");
                    if(!$stmt)throw new RuntimeException('Payment update failed.');
                    $stmt->bind_param('i',$offerId);
                    if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Payment update race.');
                    $stmt->close();
                    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Works SET SaleStatus='sold' WHERE ID=? AND SaleStatus='reserved' AND WinnerOfferID=?");
                    if(!$stmt)throw new RuntimeException('Sale update failed.');
                    $stmt->bind_param('ii',$workId,$offerId);
                    if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Sale update race.');
                    $stmt->close();
                    $action='stripe_paid';$actor='stripe';$detail=json_encode(['event_id'=>$eventId,'session_id'=>$sessionId],JSON_THROW_ON_ERROR);
                    $stmt=$db->prepare('INSERT INTO CRTSHT_Selected_Audit (SelectedWorkID,OfferID,Actor,Action,DetailJSON) VALUES (?,?,?,?,?)');
                    if(!$stmt)throw new RuntimeException('Audit insert failed.');
                    $stmt->bind_param('iisss',$workId,$offerId,$actor,$action,$detail);
                    if(!$stmt->execute())throw new RuntimeException('Audit insert failed.');
                    $stmt->close();
                    $notify=$offer;$notify['CRTSHT_ID']=$work['CRTSHT_ID'];
                }else throw new RuntimeException('Paid checkout for an ineligible or changed allocation — manual reconciliation needed.');
            }elseif($work['SaleStatus']==='reserved'&&$offer['Status']==='accepted') {
                $status=($type==='checkout.session.expired'?'expired':($type==='checkout.session.async_payment_failed'?'failed':(string)($session['payment_status']??'open')));
                $stmt=$db->prepare('UPDATE CRTSHT_Selected_Offers SET StripePaymentStatus=? WHERE ID=? AND PaidAt IS NULL');
                if(!$stmt)throw new RuntimeException('Payment status update failed.');
                $stmt->bind_param('si',$status,$offerId);if(!$stmt->execute())throw new RuntimeException('Payment status update failed.');$stmt->close();
            }
        }
        $db->commit();
    }catch(Throwable $e){$db->rollback();throw $e;}
    $mailResult=null;
    if($notify!==null){
        $mailResult=crt_selected_mail_paid($notify);
        if(!($mailResult['ok']??false))error_log('CRTSHT selected payment mail failed for '.$code.': '.($mailResult['error']??'unknown'));
    }
    return ['ok'=>true,'duplicate'=>$wasDuplicate,'mail_ok'=>$mailResult===null?null:(bool)($mailResult['ok']??false)];
}
