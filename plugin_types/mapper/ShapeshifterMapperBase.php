<?php

/**
 * @file
 * Contains \ShapeshifterMapperBase.
 */

abstract class ShapeshifterMapperBase extends \ShapeshifterPluginBase implements \ShapeshifterMapperInterface {

  /**
   * Constant to inform about an unprocessed mapping.
   */
  const UNPROCESSED = 'ShapeshifterBase::missing-value';

  /**
   * Path separator string.
   */
  const PATH_SEPARATOR = '::';

  /**
   * The output structured array.
   *
   * @var array
   */
  protected $output = array();

  /**
   * Entity type
   *
   * @var string
   */
  protected $entityType = '';

  /**
   * The loaded entity.
   *
   * @var mixed
   */
  protected $entity;

  /**
   * The ID for the entity.
   *
   * @var int
   */
  protected $entityId;

  /**
   * Set the entity id. Must be set before we can call the mapping.
   *
   * @param int $entityId
   */
  public function setEntityId($entityId) {
    // This resets the currently loaded entity.
    $this->setEntity(NULL);
    $this->entityId = $entityId;
  }

  /**
   * Gets the entity id.
   *
   * @return int
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Sets the entity
   *
   * @param mixed $entity
   */
  public function setEntity($entity) {
    $this->entity = $entity;
  }

  /**
   * Gets the entity.
   *
   * @return mixed
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Class constructor.
   *
   * @param array $plugin
   *   The plugin definition array.
   *
   * @throws \ShapeshifterMapperException
   *  When there is no entity type defined in the plugin definition.
   */
  public function __construct(array $plugin) {
    // Set the plugin.
    parent::__construct($plugin);
    if (!$this->entityType = $this->getPuginInfo('entity_type')) {
      throw new ShapeshifterMapperException('No entity type defined in the plugin definition.');
    }
    $paths = array_keys(static::getMappingsInfo());
    foreach ($paths as $path) {
      // Create the output structure array with UNPROCESSED values.
      $this->addMapping($path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function map() {
    foreach (static::getMappingsInfo() as $path => $property_info) {
      $this->addMapping($path, $this->getValue($path));
    }
    return $this->output;
  }

  /**
   * {@inheritdoc}
   */
  public function addMapping($path, $value = \ShapeshifterMapperBase::UNPROCESSED) {
    if (empty($path)) {
      if (!is_array($value)) {
        throw new \ShapeshifterMapperException('Cannot map to an empty path.');
      }
      $this->output = drupal_array_merge_deep($this->output, $value);
      return;
    }
    $path_components = explode(self::PATH_SEPARATOR, $path);
    $path_component = array_pop($path_components);
    $this->addMapping(implode(static::PATH_SEPARATOR, $path_components), array($path_component => $value));
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($path) {
    // This is where the juicy meat is.
    $mappings_info = static::getMappingsInfo();
    if (empty($mappings_info[$path])) {
      throw new \ShapeshifterMapperException(format_string('There is no mapping for the requested path "@path".', array(
        '@path' => $path,
      )));
    }

    // Get the info and add some defaults.
    $info = $mappings_info[$path] + array(
      'property' => FALSE,
      'wrapper_method' => 'value',
      'wrapper_method_on_entity' => FALSE,
      'sub_property' => FALSE,
      'process_callbacks' => array(),
      'callback' => FALSE,
    );

    $wrapper = $this->getEntityWrapper();
    $value = NULL;
    if ($info['callback']) {
      $value = static::executeCallback($info['callback'], array($wrapper));
    }
    else {
      // Exposing an entity field.
      $property = $info['property'];

      $sub_wrapper = $info['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};

      // Check user has access to the property.
      if ($property && !$this->checkPropertyAccess($sub_wrapper, 'view')) {
        throw new \ShapeshifterMapperException(format_string('Permission denied for property "@property".', array(
          '@property' => $property,
        )));
      }

      $method = $info['wrapper_method'];

      if ($sub_wrapper instanceof EntityListWrapper) {
        // Multiple value.
        foreach ($sub_wrapper as $item_wrapper) {
          if ($info['sub_property'] && $item_wrapper->value()) {
            $item_wrapper = $item_wrapper->{$info['sub_property']};
          }

          // Wrapper method.
          $value[] = $item_wrapper->{$method}();
        }
      }
      else {
        // Single value.
        if ($info['sub_property'] && $sub_wrapper->value()) {
          $sub_wrapper = $sub_wrapper->{$info['sub_property']};
        }

        // Wrapper method.
        $value = $sub_wrapper->{$method}();
      }
    }

    if ($value && !empty($info['process_callbacks'])) {
      foreach ($info['process_callbacks'] as $process_callback) {
        $value = static::executeCallback($process_callback, array($value));
      }
    }
    return $value;
  }

  /**
   * Execute a user callback.
   *
   * @param mixed $callback
   *   There are 3 ways to define a callback:
   *     - String with a function name. Ex: 'drupal_map_assoc'.
   *     - An array containing an object and a method name of that object.
   *       Ex: array($this, 'format').
   *     - An array containing any of the methods before and an array of
   *       parameters to pass to the callback.
   *       Ex: array(array($this, 'processing'), array('param1', 2))
   * @param array $params
   *   Array of additional parameters to pass in.
   *
   * @return mixed
   *   The return value of the callback.
   *
   * @throws \ShapeshifterMapperException
   */
  public static function executeCallback($callback, array $params = array()) {
    if (!is_callable($callback)) {
      if (is_array($callback) && count($callback) == 2 && is_array($callback[1])) {
        // This code deals with the third scenario in the docblock. Get the
        // callback and the parameters from the array, merge the parameters with
        // the existing ones and call recursively to reuse the logic for the
        // other cases.
        return static::executeCallback($callback[0], array_merge($params, $callback[1]));
      }
      $callback_name = is_array($callback) ? $callback[1] : $callback;
      throw new \ShapeshifterMapperException(format_string('Callback function: @callback does not exists.', array('@callback' => $callback_name)));
    }

    return call_user_func_array($callback, $params);
  }

  /**
   * Helper method to check access on a property.
   *
   * @param EntityMetadataWrapper $property
   *   The wrapped property.
   * @param $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "view".
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   */
  protected function checkPropertyAccess(EntityMetadataWrapper $property, $op = 'view') {
    $account = $this->getAccount();
    // @todo Hack to check format access for text fields. Should be removed once
    // this is handled properly on the Entity API level.
    if ($property->type() == 'text_formatted' && $property->value() && $property->format->value()) {
      $format = (object) array('format' => $property->format->value());
      if (!filter_access($format, $account)) {
        return FALSE;
      }
    }

    $info = $property->info();
    if ($op == 'edit' && empty($info['setter callback'])) {
      // Property does not allow setting.
      return FALSE;
    }

    $access = $property->access($op, $account);
    return $access === FALSE ? FALSE : TRUE;
  }

  /**
   * Helper method to get the account executing the operations.
   *
   * @return \stdClass
   *   The loosely loaded user.
   */
  protected function getAccount() {
    return $GLOBALS['user'];
  }

  /**
   * Gets an Entity Metadata Wrapper for the current entity being worked on.
   *
   * @return \EntityMetadataWrapper
   *   The wrapper.
   *
   * @throws \EntityMetadataWrapperException
   * @throws \ShapeshifterMapperException
   */
  protected function getEntityWrapper() {
    $entity = $this->getEntity();
    if (empty($entity)) {
      if (!$id = $this->getEntityId()) {
        throw new \ShapeshifterMapperException('You need to set the ID of the entity before you can map it.');
      }
      $this->setEntity(entity_load_single($this->entityType, $id));
    }
    return entity_metadata_wrapper($this->entityType, $entity);
  }

}
