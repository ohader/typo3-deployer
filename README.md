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

Example execution (assumed that `production` stage is configured in `.host.yml`)

```bash
vendor/bin/dep deploy production
```
