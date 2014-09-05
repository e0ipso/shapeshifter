<?php

/**
 * @file
 * Contains \DataMapperFormatter.
 */

abstract class DataMapperFormatterBase extends \DataMapperPluginBase implements \DataMapperFormatterInterface {

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
