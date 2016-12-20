<?php

namespace Drupal\unl_cas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UnlCasController extends ControllerBase {

  public $adapter;

  static $zendLoaded = FALSE;

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


    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    $session = \Drupal::service('session');

    // Start the session because if drupal doesn't then Zend_Session will.
    if (!$session->isStarted()) {
      /**
       * Drupal will start a 'lazy' session for anonymous users so that a cookie is not set (to help with things like varnish)
       * We can't $session->start(), because it is already lazy-started
       * Instead we need to migrate it to a stored (non-lazy) session
       */
      $session->migrate();
    }

    if (!$this->adapter) {
      if (\Drupal::request()->isSecure()) {
        $url = Url::fromRoute('unl_cas.validate', array(), array('absolute' => TRUE, 'query' => drupal_get_destination(), 'https' => TRUE))->toString();
      } else {
        $url = Url::fromRoute('unl_cas.validate', array(), array('absolute' => TRUE, 'query' => drupal_get_destination()))->toString();
      }
      $this->adapter = new \Unl_Cas($url, 'https://login.unl.edu/cas');
    }

    \Drupal::request()->query->remove('destination');

    return $this->adapter;
  }

  public function validate() {
    $cas = $this->getAdapter();

    if (array_key_exists('logoutRequest', $_POST)) {
      $cas->handleLogoutRequest($_POST['logoutRequest']);
    }

    $auth = $cas->validateTicket();

    if ($auth) {
      $username = $cas->getUsername();
      $user = $this->importUser($username);

      if (\Drupal::currentUser()->id() != $user->id()) {
        \Drupal::currentUser()->setAccount($user);
        user_login_finalize($user);
      }
    }
    else {
      if (!\Drupal::currentUser()->isAnonymous()) {
        \Drupal::currentUser()->setAccount(new AnonymousUserSession());
        $account = User::load(\Drupal::currentUser()->id());
        user_login_finalize($account);
      }
      setcookie('unl_sso', 'fake', time() - 60 * 60 * 24, '/', '.unl.edu');
    }

    $destination = drupal_get_destination();
    if (!$destination['destination']) {
      $destination['destination'] = 'admin';
    }
    $response = new RedirectResponse(Url::fromUserInput('/'.$destination['destination'])->toString());
    $response->send();
    return;
  }

  public function importUser($username) {
    $username = trim($username);
    $user = user_load_by_name($username);
    if (!$user) {
      $user = \Drupal\user\Entity\User::create();
      $user->setUsername($username);
      $user->setEmail($username . '@unl.edu');
      $user->setPassword('Trump');
      $user->enforceIsNew();
      $user->activate();
    }
    $user->save();
    return $user;
  }
}
