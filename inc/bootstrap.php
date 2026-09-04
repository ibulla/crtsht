<?php
declare(strict_types=1);

const CRTSHT_TOTAL = 128;

$privateConfig = dirname(__DIR__) . '/private/config.php';
if (is_file($privateConfig)) {
    $cfg = require $privateConfig;
    if (is_array($cfg)) {
        foreach ($cfg as $key => $value) {
            if (!is_string($key) || $key === '' || $value === null) continue;
            putenv($key . '=' . (string)$value);
            $_ENV[$key] = (string)$value;
        }
    }
    unset($cfg);
}

function crt_e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function crt_env(string $key): string { $value = getenv($key); return $value === false ? '' : trim((string)$value); }
function crt_metadata(int $id): ?array { if ($id < 1 || $id > CRTSHT_TOTAL) return null; $file = dirname(__DIR__) . '/JSON_1-128/' . $id . '.json'; if (!is_file($file)) return null; $data = json_decode((string)file_get_contents($file), true); return is_array($data) ? $data : null; }
function crt_attrs(array $meta): array { $out=[]; foreach (($meta['attributes']??[]) as $attr) if(isset($attr['trait_type'])) $out[(string)$attr['trait_type']] = (string)($attr['value']??''); return $out; }
function crt_title(int $id, ?array $meta=null): string { if($meta&&!empty($meta['name'])) return (string)$meta['name']; return '0x'.str_pad(strtolower(dechex($id)),4,'0',STR_PAD_LEFT); }
function crt_artwork(int $id): ?string { $files=glob(dirname(__DIR__).'/shitpix_jpg_1-128/'.$id.'-*.jpg'); return $files?'/shitpix_jpg_1-128/'.rawurlencode(basename($files[0])):null; }
function crt_cake(int $id): ?string { $file=dirname(__DIR__).'/coin_1-128/'.$id.'.jpg'; return is_file($file)?'/coin_1-128/'.$id.'.jpg':null; }
function crt_cid(array $meta): string { $uri=(string)($meta['image']??''); return str_starts_with($uri,'ipfs://')?substr($uri,7):''; }
function crt_db(): ?mysqli { $host=crt_env('CRTSHT_DB_HOST');$user=crt_env('CRTSHT_DB_USER');$pass=crt_env('CRTSHT_DB_PASS');$name=crt_env('CRTSHT_DB_NAME');if($host===''||$user===''||$name==='')return null;mysqli_report(MYSQLI_REPORT_OFF);$db=@new mysqli($host,$user,$pass,$name);if($db->connect_errno)return null;$db->set_charset('utf8mb4');return $db; }
function crt_db_record(int $id): ?array { $db=crt_db();if(!$db)return null;$stmt=$db->prepare('SELECT `ID`,`ShitID`,`ETH_Adr`,`Hasher_Druck`,`LoginWhirlpool`,`IPFS_COIN`,`IPFS_JSON` FROM `ShitID` WHERE `ID` = ? LIMIT 1');if(!$stmt){$db->close();return null;}$stmt->bind_param('i',$id);$stmt->execute();$result=$stmt->get_result();$row=$result?$result->fetch_assoc():null;$stmt->close();$db->close();return is_array($row)?$row:null; }
function crt_draw_assignment(int $id): ?array { if($id<1||$id>CRTSHT_TOTAL)return null;$db=crt_db();if(!$db)return null;$stmt=$db->prepare("SELECT e.ID AS EntryID,e.DrawBatch,e.AssignedAt,r.ReservationCode,r.ID AS ReservationID FROM CRTSHT_Draw_Entries e INNER JOIN CRTSHT_Draw_Reservations r ON r.ID=e.ReservationID WHERE e.AssignedCRTSHT=? AND e.Status='assigned' AND r.Status='paid' LIMIT 1");if(!$stmt){$db->close();return null;}$stmt->bind_param('i',$id);$stmt->execute();$res=$stmt->get_result();$row=$res?$res->fetch_assoc():null;$stmt->close();$db->close();return is_array($row)?$row:null; }
function crt_key_map(): array { $db=crt_db();if(!$db)return [];$result=$db->query('SELECT `ID`,`LoginWhirlpool` FROM `ShitID` WHERE `ID` BETWEEN 1 AND 128');$out=[];if($result){while($row=$result->fetch_assoc()){$id=(int)($row['ID']??0);$key=strtolower(trim((string)($row['LoginWhirlpool']??'')));if($id>=1&&$id<=CRTSHT_TOTAL&&$key!=='')$out[$id]=$key;}$result->free();}$db->close();return $out; }
function crt_http_json(string $url): ?array { $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_USERAGENT=>'CRTSHT archive/2026']);$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);if(!is_string($body)||$code<200||$code>=300)return null;$data=json_decode($body,true);return is_array($data)?$data:null; }
function crt_transfers(string $address): ?array { $key=crt_env('ETHERSCAN_API_KEY');if($address===''||$key==='')return null;$url='https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokennfttx&address='.rawurlencode($address).'&startblock=0&endblock=999999999&sort=asc&apikey='.rawurlencode($key);$data=crt_http_json($url);return $data&&($data['status']??'')==='1'&&is_array($data['result']??null)?$data['result']:null; }
function crt_mint_record(string $address): ?array { $rows=crt_transfers($address);if(!$rows)return null;foreach($rows as $row)if(is_array($row)&&strtolower((string)($row['to']??''))===strtolower($address))return $row;return is_array($rows[0]??null)?$rows[0]:null; }
function crt_rpc(string $method,array $params): ?string { $endpoints=array_values(array_unique(array_filter([crt_env('ETH_RPC_URL'),'https://cloudflare-eth.com','https://ethereum-rpc.publicnode.com'])));$payload=json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>$method,'params'=>$params]);if(!is_string($payload))return null;foreach($endpoints as $url){$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>6,CURLOPT_USERAGENT=>'CRTSHT archive/2026']);$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);if(!is_string($body)||$code<200||$code>=300)continue;$data=json_decode($body,true);if(is_array($data)&&isset($data['result'])&&is_string($data['result']))return $data['result'];}return null; }
function crt_owner_of(string $contract,string $tokenId): ?string { if(!preg_match('/^0x[a-fA-F0-9]{40}$/',$contract)||!ctype_digit($tokenId))return null;$callData='0x6352211e'.str_pad(dechex((int)$tokenId),64,'0',STR_PAD_LEFT);$result=crt_rpc('eth_call',[['to'=>$contract,'data'=>$callData],'latest']);if(!$result||!preg_match('/^0x[a-fA-F0-9]{64}$/',$result))return null;$owner='0x'.substr($result,-40);return strtolower($owner)==='0x0000000000000000000000000000000000000000'?null:$owner; }
function crt_normalize_words(array $words): array { return array_map(fn($word)=>strtolower(trim((string)$word)),$words); }
function crt_verify_words(string $title,array $words,string $stored): bool { if(!in_array('whirlpool',hash_algos(),true)||$stored===''||count($words)!==4)return false;$words=crt_normalize_words($words);foreach($words as $word)if($word==='')return false;$derived=substr(hash('whirlpool',strtolower($title).'.'.implode('.',$words)),0,8);return hash_equals(strtolower($stored),strtolower($derived)); }
function crt_oracle_matches(array $words): array { $words=crt_normalize_words($words);if(count($words)!==4)return [];foreach($words as $word)if($word==='')return [];if(!in_array('whirlpool',hash_algos(),true))return [];$keys=crt_key_map();$matches=[];foreach($keys as $id=>$stored){$meta=crt_metadata((int)$id);$title=crt_title((int)$id,$meta);$derived=substr(hash('whirlpool',strtolower($title).'.'.implode('.',$words)),0,8);if(hash_equals($stored,strtolower($derived)))$matches[]=['id'=>(int)$id,'title'=>$title,'key'=>$stored];}return $matches; }
function crt_fortune(string $key,int $id): string { $fortunes=['The object knows something the screen does not.','Keep the key. Forget the price.','A good archive never tells you where the story ends.','The future has poor metadata. Keep your own.','Something you kept will outlive something you chased.','The work changes when it leaves the wall. Let it.','Chance is only the beginning of ownership.','Trust the strange detail you almost ignored.','One block after another is still a journey.','Today is a good day to keep something offline.','Open what is sealed only when you really need it.','The secret is not the image. It is the encounter.','A key matters because something remains closed.','No algorithm can tell you why this one became yours.','The archive stays complete. The collection does not.','Ownership begins where browsing ends.','You found a number. Now give it a history.','The shortest proof is sometimes the object in your hand.'];$hash=hash('sha256',$key.'.'.$id.'.fortune');return $fortunes[hexdec(substr($hash,0,8))%count($fortunes)]; }

// Shared progressive enhancements for public CRTSHT pages and the tiny draw-control UI.
if (PHP_SAPI !== 'cli') {
    ob_start(static function (string $html): string {
        if (!str_contains($html, 'class="brand"')) return $html;

        $path = trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? ''), '/');
        $isManager = str_starts_with($path, 'crtshtdrwmng');

        // Make the manager always use an explicit index.php URL; some hosts deny directory requests.
        if ($isManager) {
            if (!str_contains($html, '/crtshtdrwmng/price.php')) {
                $html = str_replace(
                    '<a href="/draw" target="_blank">PUBLIC DRAW ↗</a>',
                    '<a href="/draw" target="_blank">PUBLIC DRAW ↗</a> &nbsp; <a href="/crtshtdrwmng/price.php">PRICE</a>',
                    $html
                );
            }
        }

        // Keep the main public navigation synchronized without duplicating edits across templates.
        if (!$isManager) {
            $navExtras = '';
            if (!str_contains($html, 'href="/draw"')) $navExtras .= '<a href="/draw">The Draw</a>';
            if (!str_contains($html, 'href="/legal"')) $navExtras .= '<a href="/legal">Legal</a>';
            if ($navExtras !== '') $html = str_replace('</nav>', $navExtras . '</nav>', $html);
        }

        // Once a paid draw ticket receives a physical CRTSHT, add that event to the public record.
        if (preg_match('~^(?:crtsht/)?(\d{1,3})$~', $path, $m) && str_contains($html, '<div class="section-head">PROVENANCE / OBJECT</div>')) {
            $assignment = crt_draw_assignment((int)$m[1]);
            if ($assignment) {
                $assignedAt = trim((string)($assignment['AssignedAt'] ?? ''));
                $drawRow = '<div class="row"><span class="label">draw entry</span><span>' . crt_e((string)$assignment['ReservationCode']) . ' · #' . str_pad((string)$assignment['EntryID'], 4, '0', STR_PAD_LEFT) . ' · DRAW ' . crt_e((string)$assignment['DrawBatch']) . '</span></div>';
                if ($assignedAt !== '') $drawRow .= '<div class="row"><span class="label">assigned</span><span>' . crt_e($assignedAt) . ' CET</span></div>';
                $html = str_replace('<div class="section-head">PROVENANCE / OBJECT</div>', '<div class="section-head">PROVENANCE / OBJECT</div>' . $drawRow, $html);
            }
        }

        if (str_contains($html, '</head>')) {
            $head = '';
            if (!str_contains($html, '/fav/favicon.ico')) {
                $head .= '<link rel="icon" type="image/x-icon" href="/fav/favicon.ico">' . "\n";
                $head .= '<link rel="icon" type="image/png" sizes="32x32" href="/fav/favicon-32x32.png">' . "\n";
                $head .= '<link rel="icon" type="image/png" sizes="16x16" href="/fav/favicon-16x16.png">' . "\n";
                $head .= '<link rel="apple-touch-icon" sizes="180x180" href="/fav/apple-touch-icon.png">' . "\n";
                $head .= '<link rel="manifest" href="/fav/site.webmanifest">' . "\n";
            }
            if (!$isManager && !str_contains($html, '/layout-tune.css')) $head .= '<link rel="stylesheet" href="/layout-tune.css?v=1">' . "\n";
            if ($head !== '') $html = str_replace('</head>', $head . '</head>', $html);
        }

        if ($path === 'draw' && str_contains($html, '</body>') && !str_contains($html, '/js/draw-price.js')) {
            $html = str_replace('</body>', '<script src="/js/draw-price.js?v=1" defer></script>' . "\n" . '</body>', $html);
        }
        if (!$isManager && str_contains($html, '</body>') && !str_contains($html, '/js/leetspeak.js')) {
            $html = str_replace('</body>', '<script src="/js/leetspeak.js?v=1" defer></script>' . "\n" . '</body>', $html);
        }
        return $html;
    });
}
