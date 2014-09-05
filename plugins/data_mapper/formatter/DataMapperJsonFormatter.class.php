<?php

/**
 * @file
 * Contains \DataMapperJsonFormatter.
 */

class DataMapperJsonFormatter extends \DataMapperFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function render(array $structured_data) {
    return drupal_json_encode($structured_data);
  }

}
