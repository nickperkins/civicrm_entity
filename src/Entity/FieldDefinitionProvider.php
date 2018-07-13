<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

class FieldDefinitionProvider implements FieldDefinitionProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinition(array $civicrm_field) {
    if ($civicrm_field['name'] == 'id') {
      $field = $this->getIdentifierDefinition();
    }
    elseif (empty($civicrm_field['type'])) {
      $field = $this->getDefaultDefinition();
    }
    else {
      switch ($civicrm_field['type']) {
        case \CRM_Utils_Type::T_INT:
          $field = $this->getIntegerDefinition($civicrm_field);
          break;

        case \CRM_Utils_Type::T_BOOLEAN:
          $field = $this->getBooleanDefinition();
          break;

        case \CRM_Utils_Type::T_MONEY:
        case \CRM_Utils_Type::T_FLOAT:
          // @todo this needs to be handled.
          $field = BaseFieldDefinition::create('float');
          break;

        case \CRM_Utils_Type::T_STRING:
          $field = $this->getStringDefinition($civicrm_field);
          break;

        case \CRM_Utils_Type::T_CCNUM:
          $field = $this->getDefaultDefinition();
          break;

        case \CRM_Utils_Type::T_TEXT:
        case \CRM_Utils_Type::T_LONGTEXT:
          $field = $this->getTextDefinition($civicrm_field);
          break;

        case \CRM_Utils_Type::T_EMAIL:
          $field = $this->getEmailDefinition();
          break;

        case \CRM_Utils_Type::T_URL:
          $field = $this->getUrlDefinition();
          break;

        case \CRM_Utils_Type::T_DATE:
          $field = $this->getDateDefinition();
          break;

        case (\CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME):
          $field = $this->getDatetimeDefinition();
          break;

        case \CRM_Utils_Type::T_ENUM:
          $field = BaseFieldDefinition::create('map');
          break;

        case \CRM_Utils_Type::T_TIMESTAMP:
          $field = $this->getTimestampDefinition();
          break;

        case \CRM_Utils_Type::T_TIME:
          // @see https://github.com/civicrm/civicrm-core/blob/master/CRM/Core/DAO.php#L279
          // When T_TIME DAO throws error?
        default:
          $field = BaseFieldDefinition::create('any');
          break;
      }
    }

    $field
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setLabel($civicrm_field['title'])
      ->setDescription(isset($civicrm_field['description']) ? $civicrm_field['description'] : '');

    if ($field->getType() != 'boolean') {
      $field->setRequired(isset($civicrm_field['api.required']) && (bool) $civicrm_field['api.required']);
    }
    if (isset($civicrm_field['api.default'])) {
      $field->setDefaultValue($field['api.default']);
    }

    return $field;
  }

  /**
   * Gets the identifier field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getIdentifierDefinition() {
    return BaseFieldDefinition::create('integer')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
  }

  /**
   * Gets an integer field definition.
   *
   * If the field uses pseudo constants, it is turned into a list_integer
   * and allowed values are set based on values that can be returned from the
   * CiviCRM API, as they are references.
   *
   * @param array $civicrm_field
   *   The CiviCRM field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getIntegerDefinition(array $civicrm_field) {
    if (!empty($civicrm_field['pseudoconstant']) && $civicrm_field['name'] != 'card_type_id') {
      $field = BaseFieldDefinition::create('list_integer')
        ->setSetting('allowed_values_function', 'civicrm_entity_pseudoconstant_options')
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'number_integer',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'options_select',
          'weight' => 0,
        ]);
    }
    // Otherwise it is just a regular integer field.
    else {
      $field = BaseFieldDefinition::create('integer')
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'number_integer',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'number',
          'weight' => 0,
        ]);
    }
    return $field;
  }

  /**
   * Gets a boolean field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getBooleanDefinition() {
    return BaseFieldDefinition::create('boolean')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 0,
      ]);
  }

  /**
   * Gets a string field definition.
   *
   * If the field uses pseudo constants, it is turned into a list_integer
   * and allowed values are set based on values that can be returned from the
   * CiviCRM API, as they are references.
   *
   * @param array $civicrm_field
   *   The CiviCRM field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getStringDefinition(array $civicrm_field) {
    if (!empty($civicrm_field['pseudoconstant'])) {
      $field = BaseFieldDefinition::create('list_string')
        ->setSetting('allowed_values_function', 'civicrm_entity_pseudoconstant_options')
        ->setDisplayOptions('view', [
          'type' => 'list_default',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'options_select',
          'weight' => 0,
        ]);
    }
    // Otherwise it is just a regular integer field.
    else {
      $field = BaseFieldDefinition::create('string')
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => 0,
        ]);
    }
    return $field;
  }

  /**
   * Gets a text field definition.
   *
   * These are long text fields, and all default to being rich text. The
   * CiviCRM API does not provide a way to identify plain text or rich text
   * fields.
   *
   * The CiviCRM field info is passed so that the method can be override to
   * provide other specific logic in different implementations.
   *
   * @param array $civicrm_field
   *   The CiviCRM field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getTextDefinition(array $civicrm_field) {
    return BaseFieldDefinition::create('text_long')
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
        // If the default text formatter is CKEditor, this will be ignored.
        'rows' => isset($civicrm_field['rows']) ? $civicrm_field['rows'] : 5,
      ]);
  }

  /**
   * Gets an email field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getEmailDefinition() {
    return BaseFieldDefinition::create('email')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 0,
      ]);
  }

  /**
   * Gets a URL field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getUrlDefinition() {
    return BaseFieldDefinition::create('uri')
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'uri_link',
        'weight' => 0,
      ]);
  }

  /**
   * Gets a date field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getDateDefinition() {
    return BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'weight' => 0,
      ]);
  }

  /**
   * Gets a datetime field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getDatetimeDefinition() {
    $field = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'weight' => 0,
      ]);
    return $field;
  }

  /**
   * Gets a timestamp field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getTimestampDefinition() {
    $field = BaseFieldDefinition::create('timestamp')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ]);
    return $field;
  }

  /**
   * Gets the default field definition.
   *
   * This is used for CiviCRM field types which are not mappable.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getDefaultDefinition() {
    return BaseFieldDefinition::create('string')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);
  }

}
