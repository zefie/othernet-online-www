This is meant to be used on a external webhost (eg Zefie Hosting, wink wink .. it should work on any host that supports PHP and PHP_CURL).

This is tested with a Othernet Dreamcatcher v3.03 running Skylark v5.5.
Updates to Skylark may break certain features of this code.

The webserver will be the one communicating with your home network, and your home IP is
not exposed to those using the Dreamcatcher.

This script won't work if its not run in the / directory of a site.

Eg mysite.com/othernet/ won't work (out of the box, probably possible with .htaccess modifications)