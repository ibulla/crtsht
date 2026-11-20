<?php
declare(strict_types=1);

/**
 * Repository-level regression check for CRTSHT Secondary Market.
 * Complements PHP lint; the payment path still requires an end-to-end staging test.
 */
$root=dirname(__DIR__);
function source(string $path): string {
    global $root;
    $body=@file_get_contents($root.'/'.$path);
    if(!is_string($body)) throw new RuntimeException("Cannot read ".$path);
    return $body;
}
function check(bool $condition,string $label): void {
    if(!$condition)throw new RuntimeException("FAILED: ".$label);
    echo "OK: ".$label."\n";
}
$public=source('selected-works.php');
$draw=source('draw-archive.php');
$admin=source('crtshtdrwmng/selected-works.php');
$schema=source('sql/crtsht_selected_works.sql');
$record=source('record.php');

check(!str_contains($public,'AND NOT EXISTS (SELECT 1 FROM CRTSHT_Draw_Entries'),
    'Historically drawn works not excluded from /selected-works');
check(!str_contains($public,'This work is already allocated.'),
    'A previous DRAW assignment does not invalidate a new purchase offer');
check(!str_contains($draw,'AND NOT EXISTS(SELECT 1 FROM CRTSHT_Draw_Entries'),
    'Historically drawn works not excluded from /draw');
check(!str_contains($admin,'Already allocated in DRAW.')
    && !str_contains($admin,'Work allocated in DRAW.'),
    'Admin may list and award previously drawn works');
check(str_contains($public,'OriginalDrawBatch')&&str_contains($draw,'OriginalDrawBatch'),
    'Public resale listings expose historical draw provenance');
check(str_contains($record,'crt_draw_assignment($id)'),
    'Individual artwork record retains original DRAW provenance');
check(str_contains($schema,'UNIQUE KEY uq_selected_crtsht'),
    'Only one current Selected Works sale cycle per artwork');
foreach (['selected-works.php','draw-archive.php','crtshtdrwmng/selected-works.php',
             'stripe/selected-start.php','inc/selected-webhook.php'] as $p) {
    $file=source($p);
    check(!preg_match('/(?:UPDATE|DELETE\\s+FROM|INSERT\\s+INTO)\\s+CRTSHT_Draw_Entries/i',$file),
        $p.' does not change original DRAW assignment rows');
}
echo "Secondary-market regression checks passed.\n";
