<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\node\Entity\NodeType;
use Drupal\oe_multilingual\LanguageNegotiationSetter;
use Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix;

/**
 * A generic Drupal context.
 */
class DrupalContext extends RawDrupalContext {

  /**
   * The config context.
   *
   * @var \Drupal\DrupalExtension\Context\ConfigContext
   */
  protected $configContext;

  /**
   * Gathers some other contexts.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The before scenario scope.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $this->configContext = $environment->getContext('Drupal\DrupalExtension\Context\ConfigContext');
  }

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
   * Redirect user to the node creation page.
   *
   * @param string $content_type_name
   *   Content type name.
   *
   * @Given I visit the :content_type_name creation page
   */
  public function iAmVisitingTheCreationPage(string $content_type_name): void {
    $node_bundle = $this->getEntityTypeByLabel($content_type_name);
    $this->visitPath('node/add/' . $node_bundle);
  }

  /**
   * Check that the field is present.
   *
   * @param string $field
   *   Input id, name or label.
   *
   * @Then I should see the field :field
   */
  public function iShouldSeeTheField(string $field): void {
    $element = $this->getSession()
      ->getPage()
      ->findField($field);
    if (empty($element)) {
      throw new \RuntimeException("Field '{$field}' is not present.");
    }
  }

  /**
   * Sets the default site language.
   *
   * @param string $name
   *   The language name.
   *
   * @Given (I set) the default site language (is) (to) :name
   */
  public function theDefaultSiteLanguageIs(string $name): void {
    $language = $this->getLanguageIdByName($name);
    $this->configContext->setConfig('system.site', 'default_langcode', $language);
  }

  /**
   * Creates and visits a test file with a given scheme.
   *
   * @param string $scheme
   *   The scheme.
   * @param string $name
   *   The file name.
   *
   * @Given I visit a test :scheme file called :name
   */
  public function createTestFile(string $scheme, string $name) {
    $file = file_save_data(file_get_contents(drupal_get_path('module', 'oe_multilingual') . '/tests/fixtures/' . $name), "$scheme://$name");
    $file->setPermanent();
    $file->save();

    $this->visitPath(file_create_url($file->getFileUri()));
    $file->delete();
  }

  /**
   * Asserts the current page is of the file with the given name.
   *
   * @param string $name
   *   The file name.
   * @param string $scheme
   *   The scheme.
   *
   * @Then the current page should be of the :name :scheme file
   */
  public function theCurrentPageShouldBeOfTheFile(string $name, string $scheme) {
    $map = [
      'public' => '\/sites\/default\/files\/' . $name,
      'private' => '\/system\/files\/' . $name,
    ];

    if (!isset($map[$scheme])) {
      throw new \Exception('Unsupported scheme');
    }

    $this->assertSession()->addressMatches("/$map[$scheme]/");
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
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $entity_type);
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
   * Configure language negotiation to use URL suffixes.
   *
   * @BeforeScenario @language-negotiation-suffix
   */
  public function setUpLanguageNegotiationSuffix(): void {
    \Drupal::service('module_installer')->install(['oe_multilingual_url_suffix']);

    $config = \Drupal::configFactory()->get(LanguageNegotiationSetter::CONFIG_NAME);

    // Replace the default prefix URL negotiator with our suffix Url negotiator
    // on the interface negotiation methods.
    $enabled_interface_methods = $config->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled');
    $enabled_interface_methods[LanguageNegotiationUrlSuffix::METHOD_ID] = $enabled_interface_methods[LanguageNegotiationUrl::METHOD_ID] ?? -19;
    unset($enabled_interface_methods[LanguageNegotiationUrl::METHOD_ID]);
    $this->configContext->setConfig(LanguageNegotiationSetter::CONFIG_NAME, 'negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled', $enabled_interface_methods);
    $this->configContext->setConfig(LanguageNegotiationSetter::CONFIG_NAME, 'negotiation.' . LanguageInterface::TYPE_INTERFACE . '.method_weights', $enabled_interface_methods);

    // Replace the default prefix URL negotiator with our suffix Url negotiator
    // on the content negotiation methods.
    $enabled_content_methods = $config->get('negotiation.' . LanguageInterface::TYPE_CONTENT . '.enabled');
    $enabled_content_methods[LanguageNegotiationUrlSuffix::METHOD_ID] = $enabled_content_methods[LanguageNegotiationUrl::METHOD_ID] ?? -19;
    unset($enabled_content_methods[LanguageNegotiationUrl::METHOD_ID]);
    $this->configContext->setConfig(LanguageNegotiationSetter::CONFIG_NAME, 'negotiation.' . LanguageInterface::TYPE_CONTENT . '.enabled', $enabled_content_methods);
    $this->configContext->setConfig(LanguageNegotiationSetter::CONFIG_NAME, 'negotiation.' . LanguageInterface::TYPE_CONTENT . '.method_weights', $enabled_content_methods);
  }

  /**
   * Revert configuration to be the same used in oe_multilingual_install().
   *
   * @AfterScenario @language-negotiation-suffix
   */
  public function revertLanguageNegotiationSuffix(): void {
    \Drupal::service('module_installer')->uninstall(['oe_multilingual_url_suffix']);
  }

}
