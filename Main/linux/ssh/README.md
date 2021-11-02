# Securing SSH

## Don't get locked out

It's very easy to lock ourselves out. After making changes to SSH, try to open a new SSH session and make sure it still works. If it doesn't, use the existing SSH session to fix the problem.

## SSH passwords

### Change root passwords

We should prepare a list of root passwords in the **Range Info** spreadsheet and change them on all Linux server as soon we can. The root passwords should be unique so that one compromised password cannot be used to login to other servers.

### Ensure empty passwords are not permitted

In `/etc/ssh/sshd_config`, we should make sure we have `PermitEmptyPasswords no`.

## SSH keys

### Enable SSH key authentication

All players who want to access Linux servers should commit their **public** SSH keys (in ~/.ssh/id_rsa.pub) to [authorized_keys](../authorized_keys), with their names clearly identified on right column.

The authorized_keys file can be quickly added to servers as follows.

```
host=<host>
cat authorized_keys | ssh root@${host} "mkdir -p /root/.ssh && chmod 700 /root/.ssh && cat > /root/.ssh/authorized_keys && chmod 600 /root/.ssh/authorized_keys && [ -f /sbin/restorecon ] && restorecon -R /root/.ssh"
```

### Disable SSH agent forwarding

If an attacker compromises root on one server, we don't want them to be able to SSH into other servers. However, with SSH agent forwarding enabled, it might be feasible to hijack an SSH agent to hop from one server to the other. To prevent this, we can disable SSH agent forwarding in `/etc/ssh/sshd_config`:

```
AllowAgentForwarding no
```

## Other SSH configurations

### Allow only specific users to SSH

Attackers might setup backdoor SSH users. To help prevent this, we can limit the users who can login via SSH in `/etc/ssh/sshd_config`. The following example allows only logins via root. If we identify other users that need SSH access, we can add them as a space separated list.

```
AllowUsers root
```

## Identify indicators of compromise

### Identify malicious authorized_keys

We can look for potentially malicious authorized_keys with the following commands:

```
find /root /home -type f -name "authorized_keys*" -exec md5sum {} \;
```

The only authorized_keys that should be present is the [authorized_keys](https://github.com/t3cht0n1c/PvJ_Captcha_This/blob/master/linux/authorized_keys) in Github. The md5sum should match. If they don't match, then they might be malicious, they might be an earlier legit authorized_keys, or they might be keys from the Gold Team. We should evaluate each case carefully. All questionable keys should be renamed rather than removed.

### Identify users with UID 0

Only root should have a UID 0:

```
[root@centos6 ~]# grep -E "^.*:.*:0:.*:.*:.*:.*$" /etc/passwd
root:x:0:0:root:/root:/bin/bash
[root@centos6 ~]#
```

### Identify users with passwords

Some user accounts may have compromised passwords. The command below can be used to see which users have passwords, which can be used for further investigation.

```
grep -e '\$1\$' /etc/shadow
```

### Identify malicious PAM modules

An attacker could create SSH backdoors using a malicious PAM module. Here is [one such example](/lib64/security/) of a backdoored pam_unix.so module. Some of the [file integrity](../fileintegrity) checks may be able to help us here.

## Upgrade the packages

### Upgrade the OpenSSH packages

We should do an apt or yum upgrade of the BIND packages. This should be a safe enough upgrade.

#### Ubuntu 16

```
apt install openssh-server
```

#### CentOS 6

```
yum install openssh-server
```

### Upgrade the packages for libraries used by OpenSSH

We could be subject to attacks on vulnerabilities in libraries used by OpenSSH. We can use these commands to upgrade those.

#### Ubuntu 16

To identify the packages to upgrade:

```
for i in $(ldd /usr/sbin/sshd | cut -d'>' -f2 | cut -d'(' -f1); do dpkg -S $i | cut -d':' -f1; done | sort | uniq | tr '\n' ' '; echo
```

To upgrade them:

```
apt install libaudit1 libc6 libcomerr2 libgcrypt20 libgpg-error0 libgssapi-krb5-2 libk5crypto3 libkeyutils1 libkrb5-3 libkrb5support0 liblzma5 libpam0g libpcre3 libselinux1 libssl1.0.0 libsystemd0 libwrap0 zlib1g
```

And then restart sshd:

```
service sshd restart
```

#### CentOS 6

To identify the packages to upgrade:

```
for i in $(ldd /usr/sbin/sshd | cut -d'>' -f2 | cut -d'(' -f1); do rpm -qf $i | sed 's/-[0-9].*//'; done | sort | uniq | tr '\n' ' '; echo
```

To upgrade them:

```
yum install audit-libs fipscheck-lib glibc keyutils-libs krb5-libs libcom_err libselinux nspr nss nss-softokn-freebl nss-util openssl pam tcp_wrappers-libs zlib
```

And then restart sshd:

```
service sshd restart
```