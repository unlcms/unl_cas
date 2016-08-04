<?php

namespace Drupal\unl_cas;

class UnlCas {

  static $adapter;

  static function getAdapter() {
    unl_load_zend_framework();

    // Start the session because if drupal doesn't then Zend_Session will.
    drupal_session_start();
    if (!UnlCas::$adapter) {
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
    $cas = UnlCas::getAdapter();

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
