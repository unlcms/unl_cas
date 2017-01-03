<?php

namespace Drupal\unl_cas\Form\MultiStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\unl_cas\Form\UserImportForm;
use Drupal\unl_cas\PersonDataQuery;

/**
 * Implements an example form.
 */
class UserImportStepOneForm extends UserImportForm {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_cas_user_import_step_one';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //We are just starting out, so delete the store...
    $this->deleteStore();
    
    $form['search'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter your search term'),
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('search')) < 3) {
      $form_state->setErrorByName('search', $this->t('The search term is too short. It must be at least 3 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = new PersonDataQuery();
    $results = $query->search($form_state->getValue('search'));
    
    if (empty($results)) {
      //No results could be found, so restart the process
      drupal_set_message($this->t('No results could be found for: @search', array('@search' => $form_state->getValue('search'))), 'error');
      $form_state->setRedirect('unl_cas.user_import');
      
      return; //exit early
    }
    
    //Results were found, continue to step two
    $this->store->set('unl_import_data', $results);
    $form_state->setRedirect('unl_cas.user_import_step_two');
  }

}
