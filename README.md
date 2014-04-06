agiliarepo
==========

New AgiliaLinux repository, which should replace a legacy one

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
  * Easy to setup (maybe, I'll even write an installer at some time)



