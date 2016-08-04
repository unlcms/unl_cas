<?php

namespace Drupal\unl_cas\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures unl_cas settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_cas_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['unl_cas.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('unl_cas.settings');

    $form['uri'] = array(
        '#type' => 'textfield',
        '#title' => 'LDAP URI',
        '#description' => 'ie: ldap://example.com/',
        '#default_value' => $config->get('uri'),
        '#required' => TRUE,
    );
    $form['dn'] = array(
        '#type' => 'textfield',
        '#title' => 'Distinguished Name (DN)',
        '#description' => 'ie: uid=admin,dc=example,dc=com',
        '#default_value' => $config->get('dn'),
        '#required' => TRUE,
    );
    $form['password'] = array(
        '#type' => 'password',
        '#title' => 'Password',
        '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('unl_cas.settings');

    $config->set('uri', $form_state->getValue('uri'));
    $config->set('dn', $form_state->getValue('dn'));
    $config->set('password', $form_state->getValue('password'));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
