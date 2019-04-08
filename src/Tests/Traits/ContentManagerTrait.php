<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Tests\Traits;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;

/**
 * Trait providing methods to manage content.
 */
trait ContentManagerTrait {

  /**
   * Return content fields array suitable for Drupal API.
   *
   * @param string $entity_type_label
   *   Content type label.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   TableNode containing a list of fields keyed by their labels.
   *
   * @return array
   *   Content fields array.
   */
  protected function getContentValues(string $entity_type_label, TableNode $table): array {
    $entity_type = $this->getEntityTypeByLabel($entity_type_label);

    $values = ['type' => $entity_type];
    foreach ($table->getRowsHash() as $field_label => $value) {
      $name = $this->getFieldNameByLabel($entity_type, $field_label);
      $values[$name] = $value;
    }

    return $values;
  }

  /**
   * Get entity type by its label.
   *
   * @param string $label
   *   Content type label.
   *
   * @return string
   *   Entity type ID.
   */
  protected function getEntityTypeByLabel(string $label): string {
    /** @var \Drupal\node\Entity\NodeType[] $entity_types */
    $entity_types = NodeType::loadMultiple();
    foreach ($entity_types as $entity_type) {
      if ($entity_type->label() === $label) {
        return $entity_type->id();
      }
    }

    throw new \InvalidArgumentException("Content type '{$label}' not found.");
  }

  /**
   * Get field name by its label.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $label
   *   Field label.
   *
   * @return string
   *   Field name.
   */
  protected function getFieldNameByLabel(string $entity_type, string $label): string {
    if ($label === 'Title') {
      return 'title';
    }

    /** @var \Drupal\Core\Field\FieldConfigBase[] $fields */
    $fields = \Drupal::entityManager()->getFieldDefinitions('node', $entity_type);
    foreach ($fields as $field) {
      if ($field instanceof FieldConfig && $field->label() === $label) {
        return $field->getName();
      }
    }

    throw new \InvalidArgumentException("Field '{$label}' not found.");
  }

  /**
   * Load an entity by label.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $label
   *   The label of the entity to load.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The loaded entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityByLabel(string $entity_type_id, string $label): ContentEntityInterface {
    $manager = \Drupal::entityTypeManager();
    $label_field = $manager->getDefinition($entity_type_id)->getKey('label');
    $entity_list = $manager->getStorage($entity_type_id)->loadByProperties([$label_field => $label]);
    return array_shift($entity_list);
  }

}
