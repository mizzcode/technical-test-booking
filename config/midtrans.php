<?php

return [
  'server_key' => env('MIDTRANS_SERVER_KEY', null),
  'client_key' => env('MIDTRANS_CLIENT_KEY', null),
  'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
  'sanitize' => env('MIDTRANS_SANITIZE', true),
  'enable_3ds' => env('MIDTRANS_3DS', true),
];