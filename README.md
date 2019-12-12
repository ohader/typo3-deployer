# TYPO3 Deployer Toolkit

> **EXPERIMENTAL** This toolkit is still experimental - create backups of remote host before using it!
>
> Currently only deployment from local DDEV to remote SSH server is supported

```bash
composer req --dev oliver-hader/typo3-remote
```

## Usage

* copy `deploy.dist.php` to project root directory as `deploy.php`
* copy `.host.dist.yml` to project root directory as `.host.yml`
* ensure `.host.yml` is **NOT** added to public Git repository
  (add to `.gitignore` file)
* adjust `.host.yml` inventory file
  (see [https://deployer.org/docs/hosts.html#inventory-file](https://deployer.org/docs/hosts.html#inventory-file))
  + special `typo3` property allow to define TYPO3 specific settings
  + `typo3/settings` is merged with `$TYPO3_CONF_VARS`
  + `typo3/databaseChanges` allows to modify remote database records (e.g. change admin password)
  + string prefix `::password-hash::` will lead to apply password hash to value (hardcoded to Argon2i currently)
  + string prefix `::random-value::` will lead to 64 random bytes being applied as hex characters

## Deploy

Example execution (assumed that `production` stage is configured in `.host.yml`)

```bash
vendor/bin/dep deploy production
```
