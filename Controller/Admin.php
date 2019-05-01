<?php

namespace ContentValidation\Controller;

use Cockpit\AuthController;

/**
 * Admin controller class.
 */
class Admin extends AuthController
{

  /**
   * Default index controller.
   */
  public function index()
  {
    if (!$this->app->module('cockpit')->hasaccess('contentvalidation', 'manage.view')) {
      return false;
    }

    $validationMessages = $this->app->module('contentvalidation')->validate();

    $dataToDisplay = [];
    foreach ($validationMessages as $error) {
        $type = $error['module'] . '-' . $error['entity'];
        if (!array_key_exists($type, $dataToDisplay)) {
            $dataToDisplay[$type] = [];
        }
        $dataToDisplay[$type] []= $error;
    }

    return $this->render('contentvalidation:views/contentvalidation/index.php', [
      'violations' => $dataToDisplay ?? [],
    ]);
  }

}
