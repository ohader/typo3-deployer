<?php
namespace Deployer;

require 'strategy/typo3-remote/from-ddev.php';

// Project name
set('application', 'your-project-name');

// [Optional] Allocate tty for git clone. Default value is false.
// This allow you to enter passphrase for keys or add host to known_hosts.
set('git_tty', true);

// DocumentRoot, public accessible through web server
set('typo3_webroot', 'public');

// Host inventory (might override previous settings)
inventory('.hosts.yml');
