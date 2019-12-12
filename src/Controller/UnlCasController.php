<?php

namespace Drupal\unl_cas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\unl_user\Helper;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UnlCasController extends ControllerBase {

  public $adapter;

  public function getAdapter() {
    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    $session = \Drupal::service('session');

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
      $options = array('hostname' => 'shib.unl.edu', 'port' => 443, 'uri' => 'idp/profile/cas');
      $protocol = new \SimpleCAS_Protocol_Version2($options);
      $this->adapter = \SimpleCAS::client($protocol);
      $this->adapter->setURL($url);
    }

    \Drupal::request()->query->remove('destination');

    return $this->adapter;
  }

  public function validate() {
    $cas = $this->getAdapter();

    if (array_key_exists('logoutRequest', $_POST)) {
      $cas->handleSingleLogOut();
    }

    $username = $cas->getProtocol()->validateTicket($cas->getTicket(), $cas->getURL());

    if ($username) {
      $helper = new Helper();
      $user = $helper->initializeUser($username);

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

    // The controller expects a response object or a render array.
    $url_raw = '/' . substr($destination['destination'], strlen(base_path()));
    $url = Url::fromUserInput($url_raw)->toString();
    return new RedirectResponse($url);
  }
}
