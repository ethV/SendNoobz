#!/usr/bin/python
#
#
# Handles emails from the config. Delivers after X amount of time
#
#
import shutil
import time
# needed for backwards compatibility of python2 vs 3 - need to convert to threading eventually
try: import thread
except ImportError: import _thread as thread
from src.core import *

# check how long to send the email
mail_time = read_config("EMAIL_FREQUENCY")

# this is what handles the loop for checking email alert frequencies


def check_alert():
    # loop forever
    while 1:
        # if the file is there, read it in then trigger email
        if os.path.isfile("/var/artillery/src/program_junk/email_alerts.log"):
            # read open the file to be sent
            fileopen = file(
                "/var/artillery/src/program_junk/email_alerts.log", "r")
            data = fileopen.read()
            if is_config_enabled("EMAIL_ALERTS"):
                send_mail("[!] Artillery has new notifications for you. [!]",
                          data)
                # save this for later just in case we need it
                shutil.move("/var/artillery/src/program_junk/email_alerts.log",
                            "/var/artillery/src/program_junk/email_alerts.old")
        time.sleep(int(mail_time))

# start a threat for checking email frequency
thread.start_new_thread(check_alert, ())
