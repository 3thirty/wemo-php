wemo-php
========

PHP interface for controlling belkin wemo devices

This is a little different from other approaches to controlling wemo devices. The idea here is that you can ask the server to turn the switches on or off and the server will ensure that this happens (eventually) in the background.

Some differences/limitations:
* we expect that you have a server (where wemo-php runs) on your network
* we do not use UPNP at all. We require known IP addresses and then just test a range of ports that wemo has been known to use
* we will retry commands that fail until they work, but it could still take minutes (hopefully not) before the switch is successful

SETUP
-----
* Clone everything into a web-accessible directory on your "server" machine (only tested on OSX)

        git clone https://github.com/3thirty/wemo-php.git ~/Sites/wemo-php

* Add your wemo IP addresses to config.inc.php (try miranda to discover these over UPNP if you don't know them, but I'd recommend setting to static IPs on your router)

* Make sure the cache file is world writable

        touch /tmp/wemo-cache
        chmod 777 /tmp/wemo-cache

* Start listener shell script in the background. This will monitor the php process and restart it as required

        ./listener.sh &

* Make sure apache is turned on and PHP is enabled (it looks like it's off by default in OSX 10.9)
    - TODO. I'm sure this is documented elsewehere on the web

* Load up the web interface:

        http://SEVER_IP/~you/wemo-php/web/

USEFUL LINKS & RESOURCES
------------------------
* http://moderntoil.com/?p=839&cpage=1 - Shell script for controlling wemo. This is mostly where the CURL commands in wemo-php come from
* http://www.techtronic.us/blog/post/technology/hacking-my-wemo-with-windows-phone/#disqus_thread - SOAP info
* http://www.issackelly.com/blog/2012/08/04/wemo-api-hacking/ - version of miranda supporting wemo devices
* http://eric-blue.com/belkin-wemo-api/ - perl library (original inspiration for this)
