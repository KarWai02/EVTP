<?php
return $GLOBALS['APP_CONFIG'] = [
  'db' => [
    'host' => '127.0.0.1',
    'name' => 'evtp_db',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
  ],

  'app' => [
    // adjust if your folder name differs
    'base_url' => '/evtp/public'
  ],

  'mail' => [
  'transport'   => 'smtp',
  'host'        => 'smtp.gmail.com',
  'port'        => 587,
  'encryption'  => 'tls',
  'username'    => getenv('EVTP_SMTP_USER'), // e.g. karw0602@gmail.com
  'password'    => getenv('EVTP_SMTP_PASS'), // your 16-char App Password
  'from_address'=> 'karw0602@gmail.com',
  'from_name'   => 'EVTP',
]
];

