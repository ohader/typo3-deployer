<?php
namespace Deployer;

// read database info from .env files into local_db_* & remote_db_*
task('typo3-remote:dotenv:load', function () {
    $projectDirectory = __DIR__;
    $hostsDirectory = __DIR__ . '/.deploy/hosts';
    $hostDotEnv = $hostsDirectory . '/' . get('hostname') . '/.env';

    if (file_exists($projectDirectory . '/.env')) {
        $localDotEnv = $projectDirectory . '/.env';
    } elseif (file_exists($projectDirectory . '/.env.dist')) {
        $localDotEnv = $projectDirectory . '/.env.dist';
    } else {
        throw new \RuntimeException(
            'No local .env or .env.dist file given in directory ' . $projectDirectory
        );
    }
    if (!file_exists($hostDotEnv)) {
        throw new \RuntimeException(
            'No host .env file given at ' . $hostDotEnv
        );
    }

    set('dotenv_file', $hostDotEnv);
    set('hosts_directory', $hostsDirectory);
    $loader = new \Symfony\Component\Dotenv\Dotenv();
    // loading .env file for local environment (basically DDEV)
    $localEnv = $loader->parse(file_get_contents($localDotEnv));
    // loading .env file for remote environment (real world server)
    $hostEnv = $loader->parse(file_get_contents($hostDotEnv));
    foreach (['NAME', 'HOST', 'USER', 'PASS', 'PORT'] as $key) {
        $lowerKey = strtolower($key);
        set('local_db_' . $lowerKey, $localEnv['TYPO3_DB_CONNECTIONS_DEFAULT_' . $key]);
        set('remote_db_' . $lowerKey, $hostEnv['TYPO3_DB_CONNECTIONS_DEFAULT_' . $key]);
    }
})->desc('Read local & remote .env files');

task('typo3-remote:dotenv:deploy', function () {
    upload('{{dotenv_file}}', '{{release_path}}/.env');
})->desc('Deploy host .env file');

task('typo3-remote:typo3:finish', function() {
    within('{{release_path}}', function () {
        // @todo .htaccess file should be part of TYPO3 CLI task
        run('if [ ! -f {{typo3_webroot}}/.htaccess ]; then cp {{typo3_webroot}}/typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/root-htaccess {{typo3_webroot}}/.htaccess; fi');
        run('vendor/bin/typo3cms install:fixfolderstructure');
        run('vendor/bin/typo3cms install:generatepackagestates');
        run('vendor/bin/typo3cms extension:setupactive');
        run('vendor/bin/typo3cms cache:flush');
    });
})->desc('Finish TYPO3 environment');
