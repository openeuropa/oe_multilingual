<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Behat;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;

/**
 * Class DrupalContext.
 */
class DrupalContext extends RawDrupalContext {

  /**
   * Create content given its type and fields.
   *
   * @Given the following :arg1 content item:
   */
  public function createContent(string $label, TableNode $table): void {
    $entity_type = $this->getEntityTypeByLabel($label);
    $node = (object) [
      'type' => $entity_type,
    ];
    foreach ($table->getRowsHash() as $label => $value) {
      $name = $this->getFieldNameByLabel($entity_type, $label);
      $node->{$name} = $value;
    }
    $this->nodeCreate($node);
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
    $entity_type_manager = \Drupal::entityTypeManager();
    $label_field = $entity_type_manager->getDefinition($entity_type_id)->getKey('label');
    $entity_list = $entity_type_manager->getStorage($entity_type_id)->loadByProperties([$label_field => $label]);
    return array_shift($entity_list);
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

    // If no field has been found then return label.
    return $label;
  }

}
