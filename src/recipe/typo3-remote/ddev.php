<?php
namespace Deployer;

// check DDEV specific requirements
task('typo3-remote:ddev:check', function() {
    if (!getenv('DDEV_URL')) {
        return;
    }
    if (!is_writable('/home/.ssh')) {
        exec(sprintf('sudo chown %s /home/.ssh', escapeshellarg(posix_getuid())));
    }
    if (!is_writable('/home/.ssh')) {
        writeln('<error>✘ Directory /home/.ssh is not writable and might lead to complictions</error>');
    }
    if (!getenv('SSH_AUTH_SOCK')) {
        writeln('<error>✘ Run "ddev auth ssh" on host machine. This requires at least DDEV version 1.4</error>');
    }
})->desc('Check DDEV specific requirements');

task('typo3-remote:ddev:local-config', function() {
    set('local_db_driver', 'pdo_mysql');
    set('local_db_name', 'db');
    set('local_db_host', 'db');
    set('local_db_user', 'db');
    set('local_db_pass', 'db');
    set('local_db_port', 3306);
})->desc('Reads local configuration from DDEV settings');
