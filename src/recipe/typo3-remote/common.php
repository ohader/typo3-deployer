<?php
namespace Deployer;

// Terminal messages and symbols
set('SymProgress', '<info>➤</info>');
set('SymInfo', '<info>✔</info>');
set('SymDeploy', '<info>⚙︎</info>');
set('SymWarning', '<info>⚠︎</info>');
set('SymError', '<error>✘</error>');
set('MsgClear', "\r\e[K\e[1A\r");

// List of shared files
set('shared_files', [
    '.env'
]);

// List of dirs which must be writable for web server
// (not used here, since it's assumed that SSH user is same as php-fpm user on remote host)
set('writable_dirs', [
//    '{{typo3_webroot}}/fileadmin',
//    '{{typo3_webroot}}/typo3temp',
//    '{{typo3_webroot}}/typo3conf',
//    '{{typo3_webroot}}/uploads'
]);

// TYPO3 specific LF constant
if (!defined('LF')) {
    define('LF', "\n");
}
