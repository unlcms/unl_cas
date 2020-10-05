<?php

namespace Drupal\unl_cas\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\unl_cas\UnlCasAdapter;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber UnlCasLoader.
 */
class UnlCasLoader implements EventSubscriberInterface {

  protected $cas;

  /**
   * A UNL CAS adapter.
   *
   * @var \Drupal\unl_cas\UnlCasAdapter
   */
  protected $unlCasAdapter;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new UnlCasLoader object.
   *
   * @param \Drupal\unl_cas\UnlCasAdapter $unl_cas_adapter
   *   A UNL CAS adapter.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The currently active route match object.
   */
  public function __construct(UnlCasAdapter $unl_cas_adapter, RouteMatchInterface $current_route_match) {
    $this->unlCasAdapter = $unl_cas_adapter;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Code that should be triggered on event specified
   */
  public function onRequest(GetResponseEvent $event) {
    // If no one is claiming to be logged in, while no one is actually logged in, and they're not trying to login - we don't need CAS.
    if (!array_key_exists('unl_sso', $_COOKIE)
        && \Drupal::currentUser()->isAnonymous()
        && $this->currentRouteMatch->getRouteName() !== 'user.login') {
      return;
    }

    // Support for the key_auth module. If an API key is present then the account and
    // permissions associated with that user takes precedence over a logged-in CAS user.
    if (\Drupal::hasService('key_auth.authentication.key_auth')) {
      $key_auth = \Drupal::service('key_auth.authentication.key_auth');
      if ($key_auth->applies($event->getRequest())) {
        return;
      }
    }

    // The current request is to the validation URL, we don't want to redirect while a login is pending.
    if ($this->currentRouteMatch->getRouteName() == 'unl_cas.validate') {
      return;
    }

    $this->cas = $this->unlCasAdapter->getAdapter();

    // Redirect the login form to CAS.
    if ($this->currentRouteMatch->getRouteName() == 'user.login') {
      // Allow redirect to be bypassed with environment variable.
      if (isset($_ENV['UNLCAS_BYPASS_LOGIN_REDIRECT'])) {
        return;
      }
      $response = new TrustedRedirectResponse($this->cas->getLoginUrl(), 302);
      $response->addCacheableDependency((new \Drupal\Core\Cache\CacheableMetadata())->setCacheMaxAge(0));
      $event->setResponse($response);
      return;
    }

    // If the user's CAS service ticket is expired, and their drupal session hasn't,
    // redirect their next GET request to CAS to keep their CAS session active.
    // However, if their drupal session expired (and they're now anonymous), redirect them regardless.
    if ($this->cas->isTicketExpired() && ($_SERVER['REQUEST_METHOD'] == 'GET' || \Drupal::currentUser()->isAnonymous())) {
      $this->cas->setGateway();
      \Drupal::request()->query->remove('destination');
      $response = new TrustedRedirectResponse($this->cas->getLoginUrl(), 302);
      $response->addCacheableDependency((new \Drupal\Core\Cache\CacheableMetadata())->setCacheMaxAge(0));
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

}
