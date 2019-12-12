<?php
namespace Deployer;

// Remove empty current directory in deploy path
task('typo3-remote:status:released', function() {
    set('typo3-remote.status.released', true);
})->desc('Status to indicate deploy:release has been invoked');
after('deploy:release', 'typo3-remote:status:released');
