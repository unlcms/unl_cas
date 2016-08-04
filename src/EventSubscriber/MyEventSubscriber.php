<?php

namespace Drupal\unl_cas\EventSubscriber;

use Drupal\unl_cas\UnlCas;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber MyEventSubscriber.
 */
class MyEventSubscriber implements EventSubscriberInterface {

  /**
   * Code that should be triggered on event specified
   */
  public function onRequest(GetResponseEvent $event) {
    // If no one is claiming to be logged in while no one is actually logged in, we don't need CAS.
    if (!array_key_exists('unl_sso', $_COOKIE) && \Drupal::currentUser()->isAnonymous()) {
      return;
    }

    // The current request is to the validation URL, we don't want to redirect while a login is pending.
    if (\Drupal::service('path.current')->getPath() == 'user/cas') {
      return;
    }

    // If the user's CAS service ticket is expired, and their drupal session hasn't,
    // redirect their next GET request to CAS to keep their CAS session active.
    // However, if their drupal session expired (and they're now anonymous), redirect them regardless.
    $cas = UnlCas::getAdapter();
    if ($cas->isTicketExpired() && ($_SERVER['REQUEST_METHOD'] == 'GET' || user_is_anonymous())) {
      $cas->setGateway();
      unset($_GET['destination']);
      drupal_goto($cas->getLoginUrl());
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
