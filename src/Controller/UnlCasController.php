<?php

namespace Drupal\unl_cas\Controller;

use Drupal\Core\Controller\ControllerBase;

class UnlCasController extends ControllerBase {

  static $adapter;

  static $zendLoaded = FALSE;

  public function content() {
    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello World! UNL CAS'),
    );
    return $build;
  }

  static function unl_load_zend_framework() {
    if (UnlCasController::$zendLoaded) {
      return;
    }

    set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../libraries');
    require_once 'Zend/Loader/Autoloader.php';
    $autoloader = \Zend_Loader_Autoloader::getInstance();
    $autoloader->registerNamespace('Unl_');
    UnlCasController::$zendLoaded = TRUE;
  }

  static function getAdapter() {
    UnlCasController::unl_load_zend_framework();

    // Start the session because if drupal doesn't then Zend_Session will.
    drupal_session_start();
    if (!UnlCasController::$adapter) {
      if (variable_get('https', FALSE)) {
        $url = url('user/cas', array('absolute' => TRUE, 'query' => drupal_get_destination(), 'https' => TRUE));
      } else {
        $url = url('user/cas', array('absolute' => TRUE, 'query' => drupal_get_destination()));
      }
      $this->adapter = new Unl_Cas($url, 'https://login.unl.edu/cas');
    }
    return $this->adapter;
  }

  static function validateTicket() {
    $cas = UnlCasController::getAdapter();

    if (array_key_exists('logoutRequest', $_POST)) {
      $cas->handleLogoutRequest($_POST['logoutRequest']);
    }

    $auth = $cas->validateTicket();

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
