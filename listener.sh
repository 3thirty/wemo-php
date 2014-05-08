#!/bin/bash

##
# simple shell script to make sure that the PHP listener is always running, one
# instance for every wemo device. This should be the *only* place that IPs for
# the wemo devices are defined
#

IPS="192.168.1.112 192.168.1.113" # IP addresses all wemo devices we want to control
LOG_FILE=/tmp/log
CACHE_FILE=/tmp/wemo-cache

while [ true ]; do
    for ip in $IPS; do
        psCount=$(ps aux | grep "listener.php $ip" | grep -v grep | wc -l | sed -e s/[^0-9]//g)

        if [ "$psCount" != "1" ]; then
            touch $LOG_FILE $CACHE_FILE
            chmod 777 $LOG_FILE $CACHE_FILE
            echo "starting listener.sh for $ip from hotloop" >> $LOG_FILE
            ./listener.php $ip > /dev/null 2>&1 &
        fi
    done

    sleep 60
done

echo "listener.sh quiting" >> $LOG_FILE
