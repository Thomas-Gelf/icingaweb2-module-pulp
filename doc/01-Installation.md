<a id="Installation"></a>Installation
=====================================

Requirements
------------

* Icinga Web 2 (&gt;= 2.4.1)
* Icinga Director (&gt; v1.5.0 current master)
* PHP (&gt;= 5.4 or 7.x)
* Pulp v2.x

> **Hint**: this module is based on some libraries provided by the Director, that's
> why you need to have a very recent version installed. We will ship those libraries
> separately in the near future to get rid of this dependency.

Installation from .tar.gz
-------------------------

Download the ~~latest version~~ (not yet) and extract it to a folder named
`pulp` in one of your Icinga Web 2 module path directories.

You might want to use a script as follows for this task:
```sh
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/Icinga/icingaweb2-module-pulp"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/pulp"
MODULE_VERSION="1.0.0"
URL="${REPO_URL}/archive/v${MODULE_VERSION}.tar.gz"
install -d -m 0755 "${TARGET_DIR}"
wget -q -O - "$URL" | tar xfz - -C "${TARGET_DIR}" --strip-components 1
```

Installation from GIT repository
--------------------------------

Another convenient method is the installation directly from our GIT repository.
Just clone the repository to one of your Icinga Web 2 module path directories.
It will be immediately ready for use:

```sh
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/Icinga/icingaweb2-module-pulp"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/pulp"
git clone "${REPO_URL}" "${TARGET_DIR}"
```

You can now directly use our current GIT master or check out a specific version.

Enable the newly installed module
---------------------------------

Enable the `pulp` module either on the CLI by running...

```sh
icingacli module enable pulp
```

...or go to your Icinga Web 2 frontend, choose `Configuration` -&gt; `Modules`
-&gt; `pulp` module - and `enable` it

Configuration
-------------

### Server list

Your `servers.ini` needs to be in this modules config directory. Usually this is
`/etc/icingaweb2/modules/pulp/servers.ini`:

```ini
[production]
api_url = "https://pulp-prod.example.com/pulp/api/"
api_username = "icinga"
api_password = "***"
; proxy = "socks://127.0.0.1:8080"
repo_url = "//mirror-prod.example.com/pulp/repos/"
alternative_repo_urls = "//mirror-dmz.example.com/pulp/repos/"

[staging]
api_url = "https://pulp-staging.example.com/pulp/api/"
api_username = "icinga"
api_password = "***"
repo_url = "//mirror-staging.example.com/pulp/repos/"
```

Fetch your configured PULP Servers repository information
---------------------------------------------------------

For now, you need to run this on demand or via a regular cron job.

    icingacli pulp fetch repos
