"""Basic HTTP deployment checks. Uses only the Python standard library."""
import os, urllib.request, urllib.error, re
base = os.environ.get('APP_URL','http://127.0.0.1:8080').rstrip('/')
def request(path):
    try:
        with urllib.request.urlopen(base+path,timeout=30) as response:
            return response.status,response.read().decode(),dict(response.headers)
    except urllib.error.HTTPError as error:
        return error.code,error.read().decode(),dict(error.headers)
for path in ['/up','/','/resources','/login','/signup','/css/app.css','/js/app.js']:
    status,body,headers=request(path)
    assert status==200,(path,status)
    print('PASS HTTP',path)
status,body,headers=request('/')
assert '<html lang="en" dir="ltr">' in body
assert 'csrf-token' in body
assert headers.get('X-Frame-Options')=='DENY'
assert headers.get('X-Content-Type-Options')=='nosniff'
for path in ['/.env','/composer.json','/database/database.sqlite','/vendor/autoload.php','/docker/runtime-key.php']:
    assert request(path)[0] in (403,404),path
    print('PASS private file protected',path)
print('HTTP deployment smoke checks passed.')
