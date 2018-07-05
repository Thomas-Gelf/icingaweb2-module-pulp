Pulp module for Icinga Web 2
============================

From [https://pulpproject.org/](https://pulpproject.org/):

> **Fetch, Upload, Organize, and Distribute Software Packages.**
>
> Pulp is a platform for managing repositories of software packages and making
> it available to a large numbers of consumers.

This is a quick & dirty module accomplishing a very few Pulp-related tasks:

* show repository status
* show current overall Pulp status

Our [Installation Instructions](doc/01-Installation.md) should help to get you
started.


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

This module has been developed as is to satisfy very specific customer needs.
In addition there is already some unused code that would allow to visualize
failed and/or ongoing background tasks. Adding some cleanup methodes (like
"drop old failed tasks") or "drop repository" (for unused/outdated ones) would
be pretty easy as well. However, **there are no plans to add more features so
far**.

> In case someone is willing to sponsor related development, this could easily
> be transformed in a full-blown web frontend for Pulp.
