<?php
return [
  'auth'       => [
      'className' => 'Auth\Module',
      'path'      => APP_PATH . '/modules/auth/Module.php',
  ],
  'categories' => [
      'className' => 'Categories\Module',
      'path'      => APP_PATH . '/modules/categories/Module.php',
  ],
  'data'       => [
      'className' => 'Data\Module',
      'path'      => APP_PATH . '/modules/data/Module.php',
  ],
  'services'   => [
      'className' => 'Services\Module',
      'path'      => APP_PATH . '/modules/services/Module.php',
  ],
  'settings'   => [
      'className' => 'Settings\Module',
      'path'      => APP_PATH . '/modules/settings/Module.php',
  ],
  'users'      => [
      'className' => 'Users\Module',
      'path'      => APP_PATH . '/modules/users/Module.php',
  ],
];