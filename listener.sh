#!/bin/bash

LOG_FILE=/tmp/log
CACHE_FILE=/tmp/wemo-cache

while [ true ]; do
    psCount=$(ps aux | grep listener.php | grep -v grep | wc -l | sed -e s/[^0-9]//g)

    if [ "$psCount" != "1" ]; then
	touch $LOG_FILE $CACHE_FILE
	chmod 777 $LOG_FILE $CACHE_FILE
	echo "starting listener.sh from hotloop" >> $LOG_FILE
        ./listener.php >> /tmp/log2 2>&1 &
    fi

    sleep 60
done

echo "listener.sh quiting" >> $LOG_FILE
