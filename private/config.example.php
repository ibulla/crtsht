<?php
/**
 * CRTSHT private configuration template.
 *
 * Copy this file on the server to:
 *   private/config.php
 *
 * Never commit private/config.php.
 */
return [
    'CRTSHT_DB_HOST' => 'localhost',
    'CRTSHT_DB_USER' => 'your_database_user',
    'CRTSHT_DB_PASS' => 'your_database_password',
    'CRTSHT_DB_NAME' => 'your_database_name',

    'ETHERSCAN_API_KEY' => 'your_etherscan_api_key',

    // Optional. Leave empty to use the public RPC fallbacks in index.php.
    'ETH_RPC_URL' => '',
];
