snmpsearch
==========

Web app used for searching switches using snmp.

To get started, copy `config.php` to `config.local.php` and then edit the variables and delete the include at the bottom.

You can then use `./macfinder.php <macaddress>` to find where the mac lives.

Web interface
==========
If you want to use the (basic) web interface, then you should create a new virtualhost on your webserver of choice with the `web` folder as the document root, and then run `git submodule update --init`. No more configuration is required.
