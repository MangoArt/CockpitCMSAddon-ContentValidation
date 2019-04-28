<?php

/**
 * @file
 * Addon admin functions.
 */

 // Module ACL definitions.
$this("acl")->addResource('errorcheck', [
  'manage.view',
  'manage.deploy',
]);

$app->on('admin.init', function () use ($app) {
  // Bind admin routes.
  $this->bindClass('ErrorCheck\\Controller\\Admin', 'errorcheck');

  if ($app->module('cockpit')->hasaccess('errorcheck', 'errorcheck.view')) {
    // Add to modules menu.
    $this('admin')->addMenuItem('modules', [
      'label' => 'Content Validation',
      'icon' => 'errorcheck:icon.svg',
      'route' => '/errorcheck',
      'active' => strpos($this['route'], '/errorcheck') === 0,
    ]);
  }
});
