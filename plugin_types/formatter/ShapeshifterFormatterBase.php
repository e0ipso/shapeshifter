<?php

/**
 * @file
 * Contains \ShapeshifterFormatter.
 */

abstract class ShapeshifterFormatterBase extends \ShapeshifterPluginBase implements \ShapeshifterFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $data) {
    return $this->render($this->prepare($data));
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array $data) {
    return $data;
  }

}
