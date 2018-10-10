<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
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
   * Enable OpenEuropa Multilingual Selection Page module.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The Hook scope.
   *
   * @BeforeScenario @selection-page
   */
  public function setupSelectionPage(BeforeScenarioScope $scope): void {
    \Drupal::service('module_installer')->install(['oe_multilingual_selection_page']);
  }

  /**
   * Disable OpenEuropa Multilingual Selection Page module.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   The Hook scope.
   *
   * @AfterScenario @selection-page
   */
  public function revertSelectionPage(AfterScenarioScope $scope): void {
    \Drupal::service('module_installer')->uninstall([
      'oe_multilingual_selection_page',
      'language_selection_page',
    ]);
  }

  /**
   * Create content given its type and fields.
   *
   * @Given the following :arg1 content item:
   */
  public function createContent(string $entity_type_label, TableNode $table): void {
    $node = (object) $this->getContentValues($entity_type_label, $table);
    $this->nodeCreate($node);
  }

  /**
   * Create translation for given content.
   *
   * @Given the following :language translation for the :entity_type_label with title :title:
   */
  public function createTranslation(string $language, string $entity_type_label, string $title, TableNode $table): void {
    // Build translation entity.
    $values = $this->getContentValues($entity_type_label, $table);
    $language = $this->getLanguageIdByName($language);
    $translation = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create($values);

    // Add the translation to the entity.
    $entity = $this->getEntityByLabel('node', $title);
    $entity->addTranslation($language, $translation->toArray())->save();

    // Make sure URL alias is correctly generated for given translation.
    $translation = $entity->getTranslation($language);
    \Drupal::service('pathauto.generator')->createEntityAlias($translation, 'insert');
  }

  /**
   * Assert viewing content given its type and title.
   *
   * @param string $title
   *   Content title.
   *
   * @Given I am visiting the :title content
   * @Given I visit the :title content
   */
  public function iAmViewingTheContent($title): void {
    $nid = $this->getEntityByLabel('node', $title)->id();
    $this->visitPath("node/$nid");
  }

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
  private function getContentValues(string $entity_type_label, TableNode $table): array {
    $entity_type = $this->getEntityTypeByLabel($entity_type_label);

    $values = ['type' => $entity_type];
    foreach ($table->getRowsHash() as $field_label => $value) {
      $name = $this->getFieldNameByLabel($entity_type, $field_label);
      $values[$name] = $value;
    }

    return $values;
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
   * Get language ID given its name.
   *
   * @param string $name
   *   Language name.
   *
   * @return string
   *   Language ID.
   */
  protected function getLanguageIdByName(string $name): string {
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      if ($language->getName() === $name) {
        return $language->getId();
      }
    }

    throw new \InvalidArgumentException("Language '{$name}' not found.");
  }

  /**
   * Redirect user to the node creation page.
   *
   * @param string $content_type_name
   *   Content type name.
   *
   * @Given I am visiting the :content_type_name creation page
   */
  public function iAmVisitingTheCreationPage(string $content_type_name): void {
    $node_bundle = $this->getEntityTypeByLabel($content_type_name);
    $this->visitPath('node/add/' . $node_bundle);
  }

  /**
   * Check that the field is not present.
   *
   * @param string $field
   *   Input id, name or label.
   *
   * @Then I should not see the field :field
   */
  public function iShouldNotSeeTheField(string $field): void {
    $element = $this->getSession()
      ->getPage()
      ->findField($field);
    if ($element) {
      throw new \RuntimeException("Field '{$field}' is present.");
    }
  }

  /**
   * Check that we have correct language for initial translation.
   *
   * @param string $title
   *   Title of node.
   *
   * @Then The only available translation for :title is in the site's default language
   */
  public function assertOnlyDefaultLanguageTranslationExist(string $title): void {
    $node = $this->getEntityByLabel('node', $title);
    if (!$node) {
      throw new \RuntimeException("Node '{$title}' is not exist.");
    }

    $node_translation_languages = $node->getTranslationLanguages();
    if (!is_array($node_translation_languages) || count($node_translation_languages) !== 1) {
      throw new \RuntimeException("We have not correct number of translations.");
    }

    $node_language = key($node_translation_languages);
    if ($node_language != \Drupal::languageManager()->getDefaultLanguage()->getId()) {
      throw new \RuntimeException("Original translation language of the '{$title}' node is not equal to site's default language.");
    }
  }

}
