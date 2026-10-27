<?php
putenv('APP_CONFIG_CACHE='.dirname(__DIR__).'/bootstrap/cache/unused_proxy_config.php');
putenv('APP_ENV=production');putenv('APP_DEBUG=false');putenv('RENDER=true');
putenv('APP_URL=https://aura-beta.example.test');
putenv('APP_KEY=base64:AQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQE=');
putenv('SESSION_DRIVER=array');putenv('CACHE_STORE=array');
putenv('SESSION_SECURE_COOKIE=true');putenv('SESSION_ENCRYPT=true');
$root=dirname(__DIR__);
require $root.'/vendor/autoload.php';$app=require $root.'/bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$request=Illuminate\Http\Request::create('http://aura-beta.example.test/login','GET',[],[],[],[
 'REMOTE_ADDR'=>'10.0.0.1','HTTP_X_FORWARDED_PROTO'=>'https','HTTP_X_FORWARDED_PORT'=>'443',
 'HTTP_X_FORWARDED_HOST'=>'attacker.example.test'
]);
$response=$kernel->handle($request);
$checks=[
 'Login page works behind HTTPS proxy'=>$response->getStatusCode()===200,
 'Render proxy HTTPS recognized'=>$request->isSecure(),
 'Forwarded host cannot change host'=>$request->getHost()==='aura-beta.example.test',
 'Form action uses HTTPS'=>str_contains($response->getContent(),'action="https://aura-beta.example.test/login"'),
 'No HTTP local asset URLs'=>!str_contains($response->getContent(),'http://aura-beta.example.test'),
 'Secure session cookie'=>(bool)array_filter($response->headers->getCookies(),fn($cookie)=>str_contains($cookie->getName(),'session')&&$cookie->isSecure()&&$cookie->isHttpOnly()),
 'Debug mode disabled'=>$app['config']->get('app.debug')===false,
];
foreach($checks as $label=>$success){if(!$success){fwrite(STDERR,"FAIL {$label}\n");exit(1);}echo "PASS {$label}\n";}
$kernel->terminate($request,$response);
