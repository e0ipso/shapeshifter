<?php

/**
 * @file
 * Contains \ShapeshifterJsonFormatter.
 */

class ShapeshifterJsonFormatter extends \ShapeshifterFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function render(array $structured_data) {
    return drupal_json_encode($structured_data);
  }

}
