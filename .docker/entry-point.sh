#!/bin/sh -e
set -e

# Set the apache user and group to match the host user.
OWNER=$(stat -c '%u' /data)
GROUP=$(stat -c '%g' /data)
if [ "$OWNER" != "0" ]; then
  deluser pktool
  addgroup -g ${GROUP} pktool
  adduser -u ${OWNER} -G pktool -D pktool
  chown -R pktool:pktool /data
fi

echo The composer user and group has been set to the following:
id pktool

cd /data
su-exec pktool php /usr/local/pktool/bin/pktool $@
