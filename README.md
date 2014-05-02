agiliarepo
==========

New AgiliaLinux repository, which should replace a legacy one which is currently running. 

Main goals and concepts:
----------------
  * Uses MongoDB to store data
  * Code separated by classes
  * Per-user storage, allowing access via ssh/ftp for package management
  * Flexible set of repositories, branches, stability classes and distro versions
  * Each package is *tagged* to belong one of repository/branch/stability class/distro version: changes does not require physical file movements
  * Support for merged repositories, which combine multiple repositories between different users
  * Easy API to access services remotely
  * Support for private repositories
  * Any package can be accessed as ABUILD instead of binary
  * Allowance of binary-free packages (e.g. ones which can be distributed only in source form)
  * Replication API: primary-slave model, public primary servers
  * Easily portable, easy to setup (maybe, I'll even write an installer at some time), which allows anyone to run a full-featured repository on his own server - as a private one, as a mirror of main repo, etc.


TODO
----
  * <del>Separate core, configuration, UI and API code by different directories - it would be good if root dir should contain only index.php (okay, stuff like README, .gitignore and .htaccess are okay there too)</del>
  * <del>Implement basic UI framework - preferably my still unimplemented and still unnamed CMFv3 :) Lots of cool ideas there, really.</del>
  * <del>User management class: add/remove/enable/disable at least</del>
  * <del>Simple authorization: simple password storage/validation will be enough for start</del>
  * <del>Understand UI structure, implement basic one</del>
  * <del>User groups, permissions</del>
  * <del>User management UI</del>
  * <del>Background worker aka TaskManager: subsysem that gets tasks from queue, do them in background, and report about tasks status and current progress</del>
  * <del>Task manager UI: task monitor, single task monitor</del>
  * <del>Package management: edit repository/os/branch/subgroup relationship (copy/move/delete)</del>
  * <del>Ability to add new packages to repository by user in some way</del>
  * <del>Version comparsion function - perhaps, should port it from old repository</del>
  * <del>Understand how API should be accessed, implement some basic queries as an example</del>
  * Repository index generation: at least old-style packages.xml.xz should be there, try to use json for this. News: JSON is very good, but mpkg wants xml for now. Json implemented in general, xml: work in progress.
  * Implement some API functions and try to use it - main goal at this point will be a test if selected API structure is fine enough, or it's ugly and should be changed
  * Think about what's better to do next :)

