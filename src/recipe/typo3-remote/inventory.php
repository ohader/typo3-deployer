<?php
namespace Deployer;

task('typo3-remote:inventory:remote-config', function() {
    $typo3Configuration = get('typo3');
    if (empty($typo3Configuration['settings']['DB']['Connections']['Default'])) {
        throw new \RuntimeException('No TYPO3 datbase default connection given in inventory');
    }

    $databaseSettings = $typo3Configuration['settings']['DB']['Connections']['Default'];
    set('remote_db_driver', $databaseSettings['driver'] ?? 'pdo_mysql');
    set('remote_db_name', $databaseSettings['dbname']);
    set('remote_db_host', $databaseSettings['host'] ?? '127.0.0.1');
    set('remote_db_user', $databaseSettings['user']);
    set('remote_db_pass', $databaseSettings['password']);
    set('remote_db_port', $databaseSettings['port'] ?? 3306);
})->desc('Reads remote configuration from TYPO3 inventory data');
