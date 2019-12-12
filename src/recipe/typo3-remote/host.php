<?php
namespace Deployer;

// Remove empty current directory in deploy path
task('typo3-remote:host:adjust', function() {
    if (test('[ -d {{deploy_path}}/current/ ]')) {
        $contents = run('find {{deploy_path}}/current/ -type f');
        if (empty($contents)) {
            run('rm -r {{deploy_path}}/current/');
        } else {
            writeln('{{SymWarning}} Directory {{deploy_path}}/current contains files...');
        }
    }
})->desc('Remove empty current directory in deploy path');
