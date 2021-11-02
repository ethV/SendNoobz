# Blue_Streak
Blue Streak Team Repo for BSLV'21 PvJ Competition


Pros vs. Joes: Blue Streak
===============================

This repository houses all code, material and files used by the the Blue Streak team for the fully remote [Pros vs. Joes] competition held from Friday, July 29th, 2021 to Saturday, July 30th, 20201 affiliated to the BSidesLV conference. 

We've coordinated this repo and continued our communication through the [Slack] channel: [https://blue-streak-workspace.slack.com](https://blue-streak-workspace.slack.com).


The Team
===========

1. __NeedAMulligan__ - Team Captain.
2. __eth5__ - Co-Captain 



The Game
===========

[PvJ] is a classic Red Team (Pros) vs. Blue Team (Joes) exercise. We act as the Blue Team to defend a computer network of different services and technologies.

The game this year will run for a continuos 36 hours.

Formal rules (at least for the previous game) are explained here: [http://prosversusjoes.net/BSidesLV2017ProsVJoesCTFrules.html](http://prosversusjoes.net/BSidesLV2017ProsVJoesCTFrules.html) Updated rules to follow when recieved from Dichotomy and the PvJ staff.

Mechanics
---------

* There will be _full Internet_ access throughout the game. The connection to the Range is done through an OpenVPN certificate.

* _The Storefront_ offers the ability to buy reverts if we get locked out of a box, upgraded firewalls, and other pro services.

* Red Team has _prior access_ to the environment. They will place "three levels" of malware drops, (1) easy-to-spot files or outbound connections, (2) more well-hidden code or scripts like in PHP files, and (3) low-level rootkits.  We can expect deep levels of persistence.

* _"Puzzle boxes"_ will be deployed throughout the game, which offer another challenge of attack and defense.

* The simulated user space "gray team" may not be instituted in this version of the game due to it being virtual.

* The scoring engine works strictly off of DNS. DNS and the PfSense firewall should be some of our top priorities. 

Reading Resources
------------

* [Game Analysis](https://blog.infosecanalytics.com/2018/08/game-analysis-of-2018-pros-vs-joes-ctf.html)
* ANY of the linked articles or material on [http://prosversusjoes.net/](http://prosversusjoes.net/)


Assumptions
---------------

We can assume we will be facing the following services:

```
ftppub
www
zcms
joomla
biz
mail
bind
```

> A Windows Server acting as a Domain Controller
>
> Several Linux servers in multiple flavors: Ubuntu, CentOS, SuSE
>
> A number of Windows workstation virtual machines

Priorities and Tools
=======================

A general playbook that may help is the Blue Team Field Manual. We will try to find a digital copy for reference, hoever if you dont already own this book, I highly recommend having it as a resource in general.

General
---------

* __DO__ network scan to find boxes that the customer did not tell you about. 
* ___DO NOT___ block ICMP, as that is necessary for scoring!!
* Change Passwords (duh)! There should be no default passwords on the game board.
* Check Network Shares. Is there anything left available (flags) that there should not be?
* Check the temp folder. `/tmp` on Linux or `%TEMPDIR%` on Windows.


* Some scripts and resources are available here: [https://github.com/4ndronicus/pros-vs-joes](https://github.com/4ndronicus/pros-vs-joes).

Domain Controller
---------------------

We do want to update PowerShell to version 5.1. 

We will need the Windows Management Framework 5.1 to be able to do this. [https://www.microsoft.com/en-us/download/details.aspx?id=54616](https://www.microsoft.com/en-us/download/details.aspx?id=54616).

To do that, we first need Microsoft .NET Framework 4.5.2. [https://www.microsoft.com/en-ca/download/confirmation.aspx?id=42642](https://www.microsoft.com/en-ca/download/confirmation.aspx?id=42642)


DNS
----------

* There is a common BIND 9 bug that is being used to consistently take down DNS and scoring.


Web Services
--------------

* __Local File Inclusion__: Test for local file inclusion on web servers. This looks like a common issue on the port `8800` service. May be a culprit if we are given the "Web Dev" box.

![http://4.bp.blogspot.com/-AgPQXKt-7Ik/VcTocWXBirI/AAAAAAAAAxo/gC6FLsbRXQ4/s1600/lfi.png](http://4.bp.blogspot.com/-AgPQXKt-7Ik/VcTocWXBirI/AAAAAAAAAxo/gC6FLsbRXQ4/s1600/lfi.png)

* __SQL Injection__: Check for SQL injection in your web applications. A culprit seen before is ZeroCMS. `sqlmap` will allow the Red Team full control...

![http://4.bp.blogspot.com/-ySMWtsN5Fhg/VcVyfBTkrbI/AAAAAAAAAyI/ILU7o-PSQoQ/s1600/Screenshot%2Bfrom%2B2015-07-20%2B00%253A43%253A21.png](http://4.bp.blogspot.com/-ySMWtsN5Fhg/VcVyfBTkrbI/AAAAAAAAAyI/ILU7o-PSQoQ/s1600/Screenshot%2Bfrom%2B2015-07-20%2B00%253A43%253A21.png)

* __phpMyAdmin__: Check if phpMyAdmin is left accessible or if default credentials are in use.

* __Command injection__: Check for simple command injection. Looks like this is common on `Contact Us` pages within some applications (they say the Subject field is what was vulnerable).

* __Log Poisoning__: can you lock site access to certain User Agents?



Pro Tips
===========

Notes shamelessly stolen from [https://systemoverlord.com/2015/08/15/blue-team-players-guide-for-pros-vs-joes-ctf/N](https://systemoverlord.com/2015/08/15/blue-team-players-guide-for-pros-vs-joes-ctf/)

> Changing all the passwords to one “standard” may be attractive, but it’ll only take one keylogger from red cell to make you regret that decision.
> Consider disabling password-based auth on the linux machines entirely, and use SSH keys instead.
> The scoring bot uses usernames and passwords to log in to some services. Changing those passwords may have an adverse effect on your scoring. Find other ways to lock down those accounts.
> Rotate roles, giving everyone a chance to go on both offense and defense.


[Pros vs. Joes]: http://prosversusjoes.net/
[PvJ]: http://prosversusjoes.net/
[Slack]: https://slack.com/
