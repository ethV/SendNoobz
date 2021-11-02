#!/bin/bash

# PvJ Blue Team setup for AIDE
#
# This requires a root SSH public/private key setup.

host="$1"
local_dir="$2"

if [ -z "$host" ] || [ -z "$local_dir" ]
then
  echo
  echo "ERROR: Please use the correct syntax."
  echo "Syntax: bash aide_setup.sh <host> <local_dir>"
  echo
  exit 1
fi

local_aide_db=$local_dir/aide.db.${host}.gz

if [ -f $local_aide_db ]
then
  echo
  echo "ERROR: $local_aide_db already exists."
  echo
  exit 1
fi

ssh root@$host "cat > /etc/aide_pvj.conf" <<EOL
# Example configuration file for AIDE.

@@define DBDIR /var/lib/aide
@@define LOGDIR /var/log/aide

# The location of the database to be read.
database=file:@@{DBDIR}/aide.db.gz

# The location of the database to be written.
#database_out=sql:host:port:database:login_name:passwd:table
#database_out=file:aide.db.new
database_out=file:@@{DBDIR}/aide.db.new.gz

# Whether to gzip the output to database
gzip_dbout=yes

# Default.
verbose=5

report_url=file:@@{LOGDIR}/aide.log
report_url=stdout
#report_url=stderr
#NOT IMPLEMENTED report_url=mailto:root@foo.com
#NOT IMPLEMENTED report_url=syslog:LOG_AUTH

# These are the default rules.
#
#p:      permissions
#i:      inode:
#n:      number of links
#u:      user
#g:      group
#s:      size
#b:      block count
#m:      mtime
#a:      atime
#c:      ctime
#S:      check for growing size
#acl:           Access Control Lists
#selinux        SELinux security context
#xattrs:        Extended file attributes
#md5:    md5 checksum
#sha1:   sha1 checksum
#sha256:        sha256 checksum
#sha512:        sha512 checksum
#rmd160: rmd160 checksum
#tiger:  tiger checksum

#haval:  haval checksum (MHASH only)
#gost:   gost checksum (MHASH only)
#crc32:  crc32 checksum (MHASH only)
#whirlpool:     whirlpool checksum (MHASH only)

#R:             p+i+n+u+g+s+m+c+acl+selinux+xattrs+md5
#L:             p+i+n+u+g+acl+selinux+xattrs
#E:             Empty group
#>:             Growing logfile p+u+g+i+n+S+acl+selinux+xattrs

# You can create custom rules like this.
# With MHASH...
# ALLXTRAHASHES = sha1+rmd160+sha256+sha512+whirlpool+tiger+haval+gost+crc32
ALLXTRAHASHES = sha1+rmd160+sha256+sha512+tiger
# Everything but access time (Ie. all changes)
EVERYTHING = R+ALLXTRAHASHES

# Sane, with multiple hashes
# NORMAL = R+rmd160+sha256+whirlpool
NORMAL = R+rmd160+sha256

# For directories, don't bother doing hashes
DIR = p+i+n+u+g+acl+selinux+xattrs

# Access control only
PERMS = p+i+u+g+acl+selinux

# Logfile are special, in that they often change
LOG = >

# Just do md5 and sha256 hashes
LSPP = R+sha256

# Some files get updated automatically, so the inode/ctime/mtime change
# but we want to know when the data inside them changes
DATAONLY =  p+n+u+g+s+acl+selinux+xattrs+md5+sha256+rmd160+tiger

# Next decide what directories/files you want in the database.

/boot   NORMAL
/bin    NORMAL
/sbin   NORMAL
/lib    NORMAL
/lib64  NORMAL
/opt    NORMAL
/usr    NORMAL
/root   NORMAL
# These are too volatile
!/usr/src
!/usr/tmp

# Check only permissions, inode, user and group for /etc, but
# cover some important files closely.
/etc    PERMS
!/etc/mtab
# Ignore backup files
!/etc/.*~
/etc/exports  NORMAL
/etc/fstab    NORMAL
/etc/passwd   NORMAL
/etc/group    NORMAL
/etc/gshadow  NORMAL
/etc/shadow   NORMAL
/etc/security/opasswd   NORMAL

/etc/hosts.allow   NORMAL
/etc/hosts.deny    NORMAL

/etc/sudoers NORMAL
/etc/skel NORMAL

/etc/logrotate.d NORMAL

/etc/resolv.conf DATAONLY

/etc/nscd.conf NORMAL
/etc/securetty NORMAL

# Shell/X starting files
/etc/profile NORMAL
/etc/bashrc NORMAL
/etc/bash_completion.d/ NORMAL
/etc/login.defs NORMAL
/etc/zprofile NORMAL
/etc/zshrc NORMAL
/etc/zlogin NORMAL
/etc/zlogout NORMAL
/etc/profile.d/ NORMAL
/etc/X11/ NORMAL

# Pkg manager
/etc/yum.conf NORMAL
/etc/yumex.conf NORMAL
/etc/yumex.profiles.conf NORMAL
/etc/yum/ NORMAL
/etc/yum.repos.d/ NORMAL

/var/log   LOG
/var/run/utmp LOG

# This gets new/removes-old filenames daily
!/var/log/sa
# As we are checking it, we've truncated yesterdays size to zero.
!/var/log/aide.log

# LSPP rules...
# AIDE produces an audit record, so this becomes perpetual motion.
# /var/log/audit/ LSPP
/etc/audit/ LSPP
/etc/libaudit.conf LSPP
/usr/sbin/stunnel LSPP
/var/spool/at LSPP
/etc/at.allow LSPP
/etc/at.deny LSPP
/etc/cron.allow LSPP
/etc/cron.deny LSPP
/etc/cron.d/ LSPP
/etc/cron.daily/ LSPP
/etc/cron.hourly/ LSPP
/etc/cron.monthly/ LSPP
/etc/cron.weekly/ LSPP
/etc/crontab LSPP
/var/spool/cron/root LSPP

/etc/login.defs LSPP
/etc/securetty LSPP
/var/log/faillog LSPP
/var/log/lastlog LSPP

/etc/hosts LSPP
/etc/sysconfig LSPP

/etc/inittab LSPP
/etc/grub/ LSPP
/etc/rc.d LSPP

/etc/ld.so.conf LSPP

/etc/localtime LSPP

/etc/sysctl.conf LSPP

/etc/modprobe.conf LSPP

/etc/pam.d LSPP
/etc/security LSPP
/etc/aliases LSPP
/etc/postfix LSPP

/etc/ssh/sshd_config LSPP
/etc/ssh/ssh_config LSPP

/etc/stunnel LSPP

/etc/vsftpd.ftpusers LSPP
/etc/vsftpd LSPP

/etc/issue LSPP
/etc/issue.net LSPP

/etc/cups LSPP

# With AIDE's default verbosity level of 5, these would give lots of
# warnings upon tree traversal. It might change with future version.
#
#=/lost\+found    DIR
#=/home           DIR

# Ditto /var/log/sa reason...
!/var/log/and-httpd

# Admins dot files constantly change, just check perms
/root/\..* PERMS
EOL

ssh root@$host "aide -c /etc/aide_pvj.conf --init"
aide_retval=$?

scp -q root@$host:/var/lib/aide/aide.db.new.gz $local_aide_db
scp_retval=$?

if [ $scp_retval -eq 0 ] && [ $aide_retval -eq 0 ]
then
  echo
  echo "OK: AIDE database saved locally to $local_aide_db."
  echo
else
  echo
  echo "ERROR: Something went wrong."
  echo
fi

exit 0
