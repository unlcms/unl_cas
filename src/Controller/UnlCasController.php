<?php

namespace Drupal\unl_cas\Controller;

use Drupal\Core\Controller\ControllerBase;

class UnlCasController extends ControllerBase {

  public function content() {
    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello World! UNL CAS'),
    );
    return $build;
  }

}
