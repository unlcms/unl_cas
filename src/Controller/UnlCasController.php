<?php

namespace Drupal\unl_cas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\unl_cas\UnlCasAdapter;
use Drupal\unl_user\Helper;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UnlCasController extends ControllerBase {

  /**
   * A UNL CAS adapter.
   *
   * @var \Drupal\unl_cas\UnlCasAdapter
   */
  protected $unlCasAdapter;

  /**
   * Provides helpers for redirect destinations.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $redirectDestination;

  /**
   * Constructs a UnlCasController object.
   *
   * @param \Drupal\unl_cas\UnlCasAdapter $unl_cas_adapter
   *   A UNL CAS adapter.
   * @param \Drupal\Core\Routing\RedirectDestination $redirect_destination
   *   Provides helpers for redirect destinations.
   */
  public function __construct(UnlCasAdapter $unl_cas_adapter, RedirectDestination $redirect_destination) {
    $this->unlCasAdapter = $unl_cas_adapter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('unl_cas.adapter'),
      $container->get('redirect.destination')
    );
  }

  public function validate() {
    $cas = $this->unlCasAdapter->getAdapter();

    if (array_key_exists('logoutRequest', $_POST)) {
      $cas->handleLogoutRequest($_POST['logoutRequest']);
    }

    $auth = $cas->validateTicket();

    if ($auth) {
      $helper = new Helper();

      $username = $cas->getUsername();
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

    $destination = $this->redirectDestination->getAsArray();
    if (!$destination['destination']) {
      $destination['destination'] = 'admin';
    }

    // The controller expects a response object or a render array.
    $url_raw = '/' . substr($destination['destination'], strlen(base_path()));
    $url = Url::fromUserInput($url_raw)->toString();
    return new RedirectResponse($url);
  }
}
