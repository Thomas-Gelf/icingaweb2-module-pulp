Pulp module for Icinga Web 2
============================

This is a quick & dirty module accomplishing a very few Pulp-related tasks:

* show current overall Pulp status
* show task status

Screenshots
-----------

### Main overview

This module shows all your repositories, content statistics, import and publish
times. It links your published repository URLs and shows warnings if any:

![Main overview](doc/screenshot/01_overview.png)

### Repository users

When such information has been provided (currently only via PuppetDB) it shows
how many (and which) servers are using a specific repository:

![Repository users](doc/screenshot/02_repousers.png)

### Menu entry

This overview is reachable via the related menu entry:

![Menu entry](doc/screenshot/03_menu.png)



Future development
------------------

So far, there are no such plans.

> In case someone is willing to sponsor related development, this could easily
> be transformed in a full-blown web frontend for Pulp.
