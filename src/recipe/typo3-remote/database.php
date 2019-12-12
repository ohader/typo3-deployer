<?php
namespace Deployer;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
use function OliverHader\TYPO3Remote\applyModifications;

// deploy local database to remote host for the initial release
task('typo3-remote:database:deploy', function() {
    if (!has('typo3-remote.status.released') || !get('typo3-remote.status.released')) {
        throw new \LogicException('typo3-remote:database:deploy must be executed after deploy:release');
    }

    $releases = get('releases_list');
    if (count($releases) === 1) {
        writeln('{{SymInfo}} Initial release found on remote host, pushing local database to remote host');
        invoke('typo3-remote:database:flush');
        invoke('typo3-remote:database:push');
    } else {
        writeln('{{SymInfo}} Release available on remote host. Skip pushing local database to remote host');
    }
})->desc('Deploy local database to remote host');

// push (deploy) local database to remote host (always, needs to be called explicitly)
task('typo3-remote:database:push', function () {
    set('database_file', sha1(uniqid()) . '.sql');
    writeln('{{SymProgress}} Dumping local database to {{database_file}}');
    // create database dump
    runLocally(
        sprintf(
            'mysqldump %s -c -h %s --port %s -u %s -p%s > %s',
            escapeshellarg(get('local_db_name')),
            escapeshellarg(get('local_db_host')),
            escapeshellarg(get('local_db_port')),
            escapeshellarg(get('local_db_user')),
            escapeshellarg(get('local_db_pass')),
            get('database_file')
        )
    );
    writeln("{{MsgClear}}{{SymInfo}}");
    // upload database dump to database directory
    writeln('{{SymProgress}} Uploading file {{database_file}} to host');
    run("cd {{deploy_path}} && if [ ! -d .dep/database ]; then mkdir -p .dep/database; fi");
    upload('{{database_file}}', '{{deploy_path}}/.dep/database/');
    writeln("{{MsgClear}}{{SymInfo}}");
    // try to import database dump
    try {
        // @todo Optional: Create database if not existing (depends on permissons)...
        writeln('{{SymProgress}} Importing local database {{local_db_name}} into remote database {{remote_db_name}}');
        $command = sprintf(
            'mysql %s -h %s --port %s -u %s -p%s',
            escapeshellarg(get('remote_db_name')),
            escapeshellarg(get('remote_db_host')),
            escapeshellarg(get('remote_db_port')),
            escapeshellarg(get('remote_db_user')),
            escapeshellarg(get('remote_db_pass'))
        );
        run(
            sprintf(
                '%s < %s',
                $command,
                get('deploy_path') . '/.dep/database/' . get('database_file')
            )
        );
        // in any case remove database dump again
        run('cd {{deploy_path}} && if [ -e .dep/database/{{database_file}} ]; then rm .dep/database/{{database_file}}; fi');
        writeln("{{MsgClear}}{{SymInfo}}");

        $backendUsers = has('typo3_backend_user') ? get('typo3_backend_user') : null;
        if (is_array($backendUsers)) {
            foreach ($backendUsers as $backendUser) {
                if (empty($backendUser['username']) || empty($backendUser['password'])) {
                    writeln('{{SymError}} Both "username" and "password" must be set for "typo3_backend_user" items');
                    continue;
                }
                run(
                    sprintf(
                        '%s -e %s',
                        $command,
                        escapeshellarg(sprintf(
                            'UPDATE be_users SET password=%s WHERE username=%s AND deleted=0',
                            escapeshellarg($backendUser['password']),
                            escapeshellarg($backendUser['username'])
                        ))
                    )
                );
                writeln('{{SymInfo}} Adjusted backend user ' . $backendUser['username']);
            }
        }

    } catch (\Exception $exception) {
        writeln("{{MsgClear}}{{SymError}}");
        throw $exception;
    } finally {
        // in any case remove database dumps again
        runLocally('if [ -e {{database_file}} ]; then rm {{database_file}}; fi');
        run('cd {{deploy_path}} && if [ -e .dep/database/{{database_file}} ]; then rm .dep/database/{{database_file}}; fi');
    }
})->desc('Push local database to remote host');

task('typo3-remote:database:flush', function () {
    $remoteDatabaseName = get('remote_db_name');
    $confirmationMessage = sprintf('This will REMOVE contents of remote database "%s". Continue?', $remoteDatabaseName);
    if (!askConfirmation($confirmationMessage)) {
        return;
    }
    $command = sprintf(
        'mysql %s -h %s --port %s -u %s -p%s',
        escapeshellarg(get('remote_db_name')),
        escapeshellarg(get('remote_db_host')),
        escapeshellarg(get('remote_db_port')),
        escapeshellarg(get('remote_db_user')),
        escapeshellarg(get('remote_db_pass'))
    );
    $tableNameList = run(
        sprintf('%s -s -e %s',
            $command,
            escapeshellarg('SHOW TABLES;')
        )
    );
    foreach (explode("\n", $tableNameList) as $tableName) {
        $tableName = trim($tableName);
        if (empty($tableName)) {
            continue;
        }
        run(
            sprintf('%s -e %s',
                $command,
                escapeshellarg(sprintf('DROP TABLE %s;', $tableName))
            )
        );
        writeln('{{SymInfo}} Dropped table ' . $tableName);
    }
})->desc('Flush database on remote host');


task('typo3-remote:database:change', function() {
    $typo3Configuration = get('typo3');
    if (empty($typo3Configuration['databaseChanges'])) {
        return;
    }
    $command = sprintf(
        'mysql %s -h %s --port %s -u %s -p%s',
        escapeshellarg(get('remote_db_name')),
        escapeshellarg(get('remote_db_host')),
        escapeshellarg(get('remote_db_port')),
        escapeshellarg(get('remote_db_user')),
        escapeshellarg(get('remote_db_pass'))
    );
    $connection = DriverManager::getConnection([
        'driver' => get('local_db_driver'),
        'dbname' => get('local_db_name'),
        'host' => get('local_db_host'),
        'port' => get('local_db_port'),
        'user' => get('local_db_user'),
        'password' => get('local_db_pass'),
    ]);
    $statements = [];
    foreach ($typo3Configuration['databaseChanges'] as $tableName => $tableChanges) {
        foreach ($tableChanges as $tableChange) {
            if (empty($tableChange['set']) || empty($tableChange['where'])
                || !is_array($tableChange['set']) || !is_array($tableChange['where'])
            ) {
                throw new \LogicException('Both `set` and `where` properties have to be defined as array');
            }
            $queryBuilder = new QueryBuilder($connection);
            $queryBuilder->update($tableName);
            foreach (applyModifications($tableChange['set']) as $key => $value) {
                $queryBuilder->set($key, $connection->quote($value));
            }
            foreach ($tableChange['where'] as $key => $value) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($key, $connection->quote($value)));
            }
            $statements[] = $queryBuilder->getSQL() . ';';
        }
    }
    foreach ($statements as $statement) {
        writeln(sprintf('{{SymDeploy}} Database: %s', $statement));
        run(
            sprintf('%s -s -e %s',
                $command,
                escapeshellarg($statement)
            )
        );
    }
})->desc('Applies changes to database records');