<?php

namespace Drupal\document_to_pdf\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides dynamic title for the DocketSubmissionForm.
 */
class DocketSubmissionController extends ControllerBase {

  /**
   * Returns the title with dashes replaced by spaces.
   */
  public function getTitle($title) {
    return str_replace('-', ' ', $title);
  }

}
