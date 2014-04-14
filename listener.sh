#!/bin/bash
while [ true ]; do
    psCount=$(ps aux | grep listener.php | grep -v grep | wc -l | sed -e s/[^0-9]//g)

    if [ "$psCount" != "1" ]; then
        chmod 777 /tmp/log
        chmod 777 /tmp/wemo-cache
        ./listener.php > /dev/null 2>&1 &
    fi

    sleep 60
done
