<?php
namespace Deployer;

require 'recipe/common.php';
require 'recipe/typo3-remote/common.php';
require 'recipe/typo3-remote/deploy.php';
require 'recipe/typo3-remote/database.php';
require 'recipe/typo3-remote/ddev.php';
// require 'recipe/typo3-remote/dotenv.php';
require 'recipe/typo3-remote/host.php';
require 'recipe/typo3-remote/inventory.php';
require 'recipe/typo3-remote/status.php';

task('deploy', [
    'deploy:info',
    // --------------------------
        'typo3-remote:ddev:check',
        'typo3-remote:host:adjust',
        'typo3-remote:ddev:local-config',
        'typo3-remote:inventory:remote-config', // @todo make switchable to .env
    'deploy:prepare',
    // --------------------------
    'deploy:lock',
    'deploy:release',
    // --------------------------
    'deploy:update_code',
        'typo3-remote:deploy:remote-config',
        'typo3-remote:database:deploy',
        'typo3-remote:database:change',
    // --------------------------
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your TYPO3 project');

after('deploy:failed', 'deploy:unlock');
after('deploy', 'success');
