<?php

/**
 * @file
 * Contains \ShapeshifterMapperInterface
 */

interface ShapeshifterMapperInterface {

  /**
   * Maps the entity to the output format.
   *
   * @return array
   *   The structured array with the selected entity info.
   *
   * @throws \ShapeshifterMapperException
   */
  public function map();

  /**
   * Gets the mappings information.
   *
   * The public fields that are exposed to the API.
   *
   *  Array with the optional values:
   *  - "property": The entity property (e.g. "title", "nid").
   *  - "sub_property": A sub property name of a property to take from it the
   *    content. This can be used for example on a text field with filtered text
   *    input format where we would need to do $wrapper->body->value->value().
   *    Defaults to FALSE.
   *  - "wrapper_method": The wrapper's method name to perform on the field.
   *    This can be used for example to get the entity label, by setting the
   *    value to "label". Defaults to "value".
   *  - "wrapper_method_on_entity": A Boolean to indicate on what to perform
   *    the wrapper method. If TRUE the method will perform on the entity (e.g.
   *    $wrapper->label()) and FALSE on the property or sub property
   *    (e.g. $wrapper->field_reference->label()). Defaults to FALSE.
   *  - "callback": A callable callback to get a computed value. Defaults To
   *    FALSE.
   *    The callback function receive as first argument the entity
   *    EntityMetadataWrapper object.
   *  - "process_callbacks": A callable callbacks to perform on the returned
   *    value, or an array with the object and method. Defaults To array().
   *
   * @return array
   *   Returns an array with the mapping paths and the source of the data.
   */
  public static function getMappingsInfo();

  /**
   * Sets a value in the output array.
   *
   * @param string $path
   *   The path to the output structured array. Separate nested arrays using ::.
   *   The value to map will be determined by static::getMappingsInfo().
   *   Example 'images::primary::caption' will map to:
   *   array(
   *     ...
   *     'images' => array(
   *       ...
   *       'primary' => array(
   *         'caption' => $value,
   *       ),
   *       ...
   *     ),
   *     ...
   *   );
   * @param mixed $value
   *   The value to set. If no value is set then the array structure will be
   *   created and filled with UNPROCESSED.
   *
   * @return void
   *
   * @throws \ShapeshifterMapperException
   *   When the structure for the path is not set.
   */
  public function addMapping($path, $value = \ShapeshifterMapperBase::UNPROCESSED);

  /**
   * Gets the value from the entity for the passed in path.
   *
   * @param string $path
   *   The path serving as identifier to get the value.
   *
   * @return mixed
   *   The data value in the entity.
   *
   * @throws \ShapeshifterMapperException
   *
   * @see \ShapeshifterMapperInterface::getMappingsInfo().
   */
  public function getValue($path);

}
