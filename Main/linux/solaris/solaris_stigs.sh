#!/bin/bash



echo "V-756"
rm -f /etc/default/sulogin


echo "V-761: Check for duplicate account names"
passwd -sa | sort | uniq -c | awk '$1 > 1 {print $2}'


echo "V-762: Check for duplicate UIDs"
logins -d


echo "V-765: Determine if successful/unsuccessful logons are being logged."
last | more
more /var/adm/loginlog
echo 'auth.* /var/log/authlog' >> /etc/syslog.conf
