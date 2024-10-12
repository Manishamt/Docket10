<?php

namespace Drupal\document_to_pdf\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom docket submission form block.
 *
 * @Block(
 *   id = "document_to_pdf_docket_submission_form_block",
 *   admin_label = @Translation("Docket Submission Form Block"),
 * )
 */
class DocketSubmissionFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the form.
    $current_path = \Drupal::service('path.current')->getPath();
    $form = \Drupal::formBuilder()->getForm('Drupal\document_to_pdf\Form\DocketSubmissionForm');

    // Return the form render array.
    return $form;
  }

}
