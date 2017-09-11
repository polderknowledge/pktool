#!/bin/sh -e

# Stop on errors
set -e

# The user and group that is currently executing
OWNER=$(stat -c '%u' /data)
GROUP=$(stat -c '%g' /data)

if [ "$OWNER" != "0" ]; then
	usermod -o -u "$OWNER" pktool 2> /dev/null
	groupmod -o -g "$GROUP" pktool 2> /dev/null
fi

usermod -s /bin/bash pktool 2> /dev/null

gosu pktool "${@}"
