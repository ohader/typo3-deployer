<?php
namespace Deployer;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use function OliverHader\TYPO3Remote\applyModifications;

task('typo3-remote:deploy:remote-config', function () {
    if (empty(get('release_path'))) {
        throw new \RuntimeException('Invalid task sequence, release_path not defined');
    }

    $temporaryLocalConfiguration = sha1(uniqid()) . 'LocalConfiguration.php';
    $currentLocalConfiguration = get('typo3_webroot') . '/typo3conf/LocalConfiguration.php';
    $configuration = require $currentLocalConfiguration;
    $configuration = array_replace_recursive($configuration, applyModifications(get('typo3')['settings'] ?? []));
    $configuration = ArrayUtility::sortByKeyRecursive($configuration);
    file_put_contents($temporaryLocalConfiguration, implode(PHP_EOL, [
        '<?php',
        'return ' . ArrayUtility::arrayExport($configuration) . ';',
        ''
    ]));

    try {
        if (test('[ ! -d {{release_path}}/{{typo3_webroot}}/typo3conf/ ]')) {
            writeln('{{SymInfo}} Creating directory {{release_path}}/{{typo3_webroot}}/typo3conf/');
            run('mkdir -p {{release_path}}/{{typo3_webroot}}/typo3conf/');
        }
        writeln('{{SymInfo}} Deploying configuration to {{release_path}}/{{typo3_webroot}}/typo3conf/LocalConfiguration.php');
        upload($temporaryLocalConfiguration, '{{release_path}}/{{typo3_webroot}}/typo3conf/LocalConfiguration.php');
    } catch (\Exception $exception) {
        throw $exception;
    } finally {
        if (file_exists($temporaryLocalConfiguration)) {
            unlink($temporaryLocalConfiguration);
        }
    }
})->desc('Adjusts LocalConfiguration.php on remote host with settings from inventory');