<?php

namespace Drupal\unl_cas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class UnlCasController extends ControllerBase {

  public $adapter;

  static $zendLoaded = FALSE;

  public function content() {
    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello World! UNL CAS'),
    );
    return $build;
  }

  public function unl_load_zend_framework() {
    if (UnlCasController::$zendLoaded) {
      return;
    }

    set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../libraries');
    require_once 'Zend/Loader/Autoloader.php';
    $autoloader = \Zend_Loader_Autoloader::getInstance();
    $autoloader->registerNamespace('Unl_');
    UnlCasController::$zendLoaded = TRUE;
  }

  public function getAdapter() {
    $this->unl_load_zend_framework();

    // Start the session because if drupal doesn't then Zend_Session will.
    $session = \Drupal::service('session');
    $session->start();

    if (!$this->adapter) {
      if (!\Drupal::request()->isSecure()) {
        $url = Url::fromRoute('unl_cas.validate_ticket', array(), array('absolute'=>TRUE, 'https'=>TRUE))->toString();
      } else {
        $url = Url::fromRoute('unl_cas.validate_ticket', array(), array('absolute'=>TRUE))->toString();
      }
      $this->adapter = new \Unl_Cas($url, 'https://login.unl.edu/cas');
    }
    return $this->adapter;
  }

  public function validateTicket() {

    if (array_key_exists('logoutRequest', $_POST)) {
      $this->adapter->handleLogoutRequest($_POST['logoutRequest']);
    }

    $auth = $this->adapter->validateTicket();

    if ($auth) {
      $username = $cas->getUsername();
      $user = unl_cas_import_user($username);

      if ($GLOBALS['user']->uid != $user->uid) {
        $GLOBALS['user'] = $user;
        user_login_finalize();
      }
    }
    else {
      if (!user_is_anonymous()) {
        $GLOBALS['user'] = drupal_anonymous_user();
        user_login_finalize();
      }
      setcookie('unl_sso', 'fake', time() - 60 * 60 * 24, '/', '.unl.edu');
    }

    $destination = drupal_get_destination();
    unset($_GET['destination']);
    drupal_goto($destination['destination']);
  }

}
