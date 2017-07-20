#!/bin/sh -e

# Stop on errors
set -e

# The user and group that is currently executing
OWNER=$(stat -c '%u' /data)
GROUP=$(stat -c '%g' /data)

# When the owner is not root, let's create a user in the container
if [ "$OWNER" != "0" ]; then
    deluser pktool
    addgroup --gid ${GROUP} pktool
    adduser --uid ${OWNER} --no-create-home --disabled-password --ingroup pktool --gecos pktool pktool
    chown -R pktool:pktool /data
fi

echo The composer user and group has been set to the following:

id pktool

cd /data

php /usr/local/pktool/bin/pktool $@
