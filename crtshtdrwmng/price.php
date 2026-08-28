<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('crtsht_draw_admin');
session_set_cookie_params(['lifetime'=>0,'path'=>'/crtshtdrwmng','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Strict']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['draw_admin_ok'])) { header('Location: /crtshtdrwmng/index.php', true, 303); exit; }

function pe(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function pcsrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24)); return (string)$_SESSION['csrf']; }
function pcsrf_ok(string $v): bool { return $v!=='' && hash_equals((string)($_SESSION['csrf']??''),$v); }

$db=crt_db(); if(!$db){http_response_code(503);exit('Database unavailable.');}
$message='';$error='';
$rid=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!pcsrf_ok((string)($_POST['csrf']??''))){$error='Security token expired.';}
    else{
        $action=(string)($_POST['action']??'');
        try{
            if($action==='global_prices'){
                $vals=[];
                foreach([1,2,3] as $q){
                    $p=filter_var($_POST['price_'.$q]??null,FILTER_VALIDATE_FLOAT);
                    if($p===false||$p<0||$p>999999)throw new RuntimeException('Invalid price for '.$q.'×.');
                    $vals[$q]=number_format((float)$p,2,'.','');
                }
                $stmt=$db->prepare("INSERT INTO CRTSHT_Draw_Settings (SettingKey,SettingValue) VALUES (?,?) ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)");
                if(!$stmt)throw new RuntimeException('Prepare failed.');
                foreach($vals as $q=>$value){$key='price_'.$q.'_chf';$stmt->bind_param('ss',$key,$value);$stmt->execute();}
                $stmt->close();$message='Public draw prices updated.';
            }elseif($action==='reservation_price'){
                $id=filter_var($_POST['reservation_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                $total=filter_var($_POST['total_price']??null,FILTER_VALIDATE_FLOAT);
                if(!$id||$total===false||$total<0||$total>999999)throw new RuntimeException('Invalid reservation price.');
                $stmt=$db->prepare('UPDATE CRTSHT_Draw_Reservations SET TotalPrice=?,UnitPrice=CASE WHEN Quantity>0 THEN ?/Quantity ELSE 0 END WHERE ID=?');
                if(!$stmt)throw new RuntimeException('Prepare failed.');
                $t=(float)$total;$stmt->bind_param('ddi',$t,$t,$id);$stmt->execute();$stmt->close();$message='Reservation package price updated.';$rid=(int)$id;
            }
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}

$prices=[1=>'0.00',2=>'0.00',3=>'0.00'];
$res=$db->query("SELECT SettingKey,SettingValue FROM CRTSHT_Draw_Settings WHERE SettingKey IN ('price_1_chf','price_2_chf','price_3_chf')");
if($res){while($row=$res->fetch_assoc()){if(preg_match('/^price_([123])_chf$/',(string)$row['SettingKey'],$m))$prices[(int)$m[1]]=(string)$row['SettingValue'];}$res->free();}
$reservation=null;if($rid){$stmt=$db->prepare('SELECT ID,ReservationCode,Quantity,UnitPrice,TotalPrice,Status FROM CRTSHT_Draw_Reservations WHERE ID=? LIMIT 1');$stmt->bind_param('i',$rid);$stmt->execute();$reservation=$stmt->get_result()->fetch_assoc()?:null;$stmt->close();}
$db->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CRTSHT / PRICE CONTROL</title><style>:root{--bg:#f2f2ee;--fg:#111;--muted:#777;--line:#c9c9c2}*{box-sizing:border-box}html{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;background:var(--bg);color:var(--fg)}body{margin:0;padding:18px}a{color:inherit}.top{display:flex;justify-content:space-between;gap:18px;border-bottom:1px solid var(--fg);padding-bottom:12px;margin-bottom:24px}.brand{font-size:clamp(24px,4vw,46px);font-weight:800;letter-spacing:-.07em;text-decoration:none}.wrap{max-width:860px}.box{border:1px solid var(--fg);padding:16px;margin-bottom:18px}.box h2{font-size:clamp(26px,4vw,48px);letter-spacing:-.05em;margin:0 0 14px}.small{font-size:10px;text-transform:uppercase;letter-spacing:.08em}.muted{color:var(--muted)}label{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.07em;margin-bottom:7px}input{font:inherit;width:100%;border:1px solid var(--line);background:transparent;padding:11px}button{font:inherit;background:var(--fg);color:var(--bg);border:0;padding:11px 14px;cursor:pointer;margin-top:10px}.flash{border:1px solid var(--fg);padding:10px 12px;margin-bottom:16px;font-size:11px}.bad{background:var(--fg);color:var(--bg)}.prices{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:10px 0 18px}.pricecard{border:1px solid var(--line);padding:12px}.pricecard b{display:block;font-size:clamp(28px,4vw,52px);letter-spacing:-.06em}.pricecard span{font-size:10px;text-transform:uppercase}.pricefields{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.row{display:grid;grid-template-columns:150px 1fr;gap:12px;padding:8px 0;border-bottom:1px solid var(--line);font-size:11px}@media(max-width:650px){.prices,.pricefields{grid-template-columns:1fr}.row{grid-template-columns:1fr}}</style></head><body><div class="wrap"><div class="top"><a class="brand" href="/crtshtdrwmng/index.php">CRTSHT / PRICE CONTROL</a><div class="small"><a href="/crtshtdrwmng/index.php">DRAW CONTROL</a></div></div><?php if($message):?><div class="flash"><?=pe($message)?></div><?php endif;?><?php if($error):?><div class="flash bad"><?=pe($error)?></div><?php endif;?><div class="box"><div class="small">PUBLIC DRAW PACKAGES</div><div class="prices"><?php foreach([1,2,3] as $q):?><div class="pricecard"><span><?=$q?>× ENTRY<?=$q>1?'IES':''?></span><b>CHF <?=pe(number_format((float)$prices[$q],0,'.',"'"))?></b></div><?php endforeach;?></div><form method="post"><input type="hidden" name="csrf" value="<?=pe(pcsrf())?>"><input type="hidden" name="action" value="global_prices"><div class="pricefields"><?php foreach([1,2,3] as $q):?><label><?=$q?>× package / CHF<input type="number" name="price_<?=$q?>" min="0" step="0.01" value="<?=pe($prices[$q])?>" required></label><?php endforeach;?></div><button type="submit">UPDATE PUBLIC PRICES</button></form><p class="small muted">Each package price is independent. A reservation snapshots the selected package price at the moment it is created.</p></div><?php if($reservation):?><div class="box"><div class="small">RESERVATION OVERRIDE</div><h2><?=pe($reservation['ReservationCode'])?></h2><div class="row"><span>Quantity</span><strong><?=pe((string)$reservation['Quantity'])?>×</strong></div><div class="row"><span>Effective per entry</span><strong>CHF <?=pe(number_format((float)$reservation['UnitPrice'],2,'.',"'"))?></strong></div><div class="row"><span>Stored package total</span><strong>CHF <?=pe(number_format((float)$reservation['TotalPrice'],2,'.',"'"))?></strong></div><form method="post"><input type="hidden" name="csrf" value="<?=pe(pcsrf())?>"><input type="hidden" name="action" value="reservation_price"><input type="hidden" name="reservation_id" value="<?=$reservation['ID']?>"><label>Override package total / CHF<input type="number" name="total_price" min="0" step="0.01" value="<?=pe((string)$reservation['TotalPrice'])?>" required></label><button type="submit">SAVE RESERVATION PRICE</button></form><p class="small"><a href="/crtshtdrwmng/index.php?id=<?=$reservation['ID']?>">← BACK TO RESERVATION</a></p></div><?php endif;?><div class="box"><div class="small">OPEN RESERVATION PRICE</div><form method="get"><label>Reservation ID<input type="number" name="id" min="1" required></label><button type="submit">OPEN</button></form></div></div></body></html>