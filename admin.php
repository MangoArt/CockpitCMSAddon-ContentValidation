<?php

/**
 * @file
 * Addon admin functions.
 */

 // Module ACL definitions.
$this("acl")->addResource('contentvalidation', [
  'manage.view',
]);

$app->on('admin.init', function () use ($app) {
  // Bind admin routes.
  $this->bindClass('ContentValidation\\Controller\\Admin', 'contentvalidation');

  // if ($app->module('cockpit')->hasaccess('contentvalidation', 'manage.view')) {
    // Add to modules menu.
    $this('admin')->addMenuItem('modules', [
      'label' => 'Content Validation',
      'icon' => 'contentvalidation:icon.svg',
      'route' => '/contentvalidation',
      'active' => strpos($this['route'], '/contentvalidation') === 0,
    ]);
  // }
});
