<?php

/**
 * @file
 * Contains \ShapeshifterXmlFormatter.
 */

class ShapeshifterXmlFormatter extends \ShapeshifterFormatterBase {

  /**
   * XML needs a single container element.
   *
   * @var string
   */
  protected $rootElement = '<data/>';

  /**
   * Change the root element.
   *
   * @param string $rootElement
   *   The new element to set. Must be valid XML. Ex: '<root/>'.
   */
  public function setRootElement($rootElement) {
    $this->rootElement = $rootElement;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $structured_data) {
    return $this
      ->arrayToXML($structured_data, new SimpleXMLElement($this->rootElement))
      ->asXML();
  }

  /**
   * Converts the input array into an XML formatted string.
   *
   * @param array $data
   *   The input array.
   * @param SimpleXMLElement $xml
   *   The object that will perform the conversion.
   *
   * @return SimpleXMLElement
   */
  protected function arrayToXML(array $data, SimpleXMLElement $xml) {
    foreach ($data as $key => $value) {
      if (is_object($value)) {
        // Cast objects to arrays.
        $value = (array) $value;
      }
      if (is_array($value)) {
        $sub_node = $xml->addChild($this->sanitizeKey($key));
        $this->arrayToXML($value, $sub_node);
      }
      else {
        $xml->addChild($this->sanitizeKey($key), $value);
      }
    }

    return $xml;
  }

  /**
   * Shapeshifters a key to be a valid XML node.
   *
   * @param mixed $key
   *   The key to sanitize.
   *
   * @return string
   *   The sanitized key.
   */
  protected function sanitizeKey($key) {
    if (ctype_digit((string) $key)){
      // XML does not allow numeric keys.
      return 'item-' . $key;
    }
    if (empty($key)) {
      return 'item';
    }
    return $key;
  }

}
