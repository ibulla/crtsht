<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/**
 * Store and compare Selected Works DATETIME values in Europe/Zurich local time.
 * The legacy DRAW connection is not modified. PHP computes the current DST offset.
 */
function crt_selected_db_timezone(mysqli $db): void {
    $offset=(new DateTimeImmutable('now',new DateTimeZone('Europe/Zurich')))->format('P');
    if(!$db->query("SET time_zone = '" . $offset . "'"))
        throw new RuntimeException('Selected Works timezone could not be set.');
}

/** Serializes assignments across DRAW and Selected Works without schema changes. */
function crt_selected_lock_artwork(mysqli $db,int $id): void {
    if($id<1||$id>CRTSHT_TOTAL)throw new RuntimeException('Invalid CRTSHT artwork.');
    $key='crtsht-artwork-'.(string)$id;
    $stmt=$db->prepare('SELECT GET_LOCK(?, 8) AS got');
    if(!$stmt)throw new RuntimeException('Artwork lock unavailable.');
    $stmt->bind_param('s',$key);
    if(!$stmt->execute())throw new RuntimeException('Artwork lock failed.');
    $row=$stmt->get_result()->fetch_assoc();$stmt->close();
    if((int)($row['got']??0)!==1)throw new RuntimeException('Artwork temporarily busy. Please retry.');
}
function crt_selected_unlock_artwork(mysqli $db,int $id): void {
    if($id<1||$id>CRTSHT_TOTAL)return;
    $key='crtsht-artwork-'.(string)$id;
    $stmt=$db->prepare('SELECT RELEASE_LOCK(?)');
    if($stmt){$stmt->bind_param('s',$key);$stmt->execute();$stmt->close();}
}
