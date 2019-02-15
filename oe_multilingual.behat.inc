<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Tests\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Core\Language\LanguageInterface;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\locale\SourceString;
use Drupal\oe_multilingual\Tests\Traits\ContentManagerTrait;
use PHPUnit\Framework\Assert;
use Behat\Gherkin\Node\TableNode;

/**
 * Provides step to test functionality provided by oe_multilingual.
 */
class OeMultilingualContext extends DrupalSubContextBase implements DrupalSubContextInterface {
  use ContentManagerTrait;
  /**
   * The backed up translations.
   *
   * @var array
   */
  protected $translations = [];

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
   * Restores backed up translations.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $afterScenarioScope
   *   The scope.
   *
   * @AfterScenario
   */
  public function restoreTranslations(AfterScenarioScope $afterScenarioScope): void {
    if (!$this->translations) {
      return;
    }

    /** @var \Drupal\locale\StringStorageInterface $locale_storage */
    $locale_storage = \Drupal::service('locale.storage');

    foreach ($this->translations as $info) {
      /** @var \Drupal\Core\Language\LanguageInterface $language */
      $language = $info['language'];
      /** @var \Drupal\locale\TranslationString $translation */
      $translation = $info['translation'];

      // If there is a translation value, we need to restore it by simply
      // giving it a save since we had cloned it.
      if ($translation->isTranslation()) {
        $translation->save();
        continue;
      }

      // Otherwise, we need to delete the translation for that source.
      $locale_storage->deleteTranslations(['lid' => $translation->getId(), 'language' => $language->getId()]);
    }
  }

  /**
   * Get language given its name.
   *
   * @param string $name
   *   Language name.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The language.
   */
  protected function getLanguageByName(string $name): LanguageInterface {
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      if ($language->getName() === $name) {
        return $language;
      }
    }

    throw new \InvalidArgumentException(sprintf('Language %s not found.', $name));
  }

  /**
   * Translates an interface string.
   *
   * @param string $string
   *   The string to translate.
   * @param string $language
   *   The language to translate into.
   * @param string $value
   *   What to translate to.
   *
   * @throws \Exception
   *   Throws an exception if the string to translate is not found.
   *
   * @Given I translate :string in :language to :value
   */
  public function translateString(string $string, string $language, string $value): void {
    /** @var \Drupal\locale\StringStorageInterface $locale_storage */
    $locale_storage = \Drupal::service('locale.storage');
    $language = $this->getLanguageByName($language);

    $source = $locale_storage->findString(['source' => $string]);
    if (!$source instanceof SourceString) {
      throw new \Exception(sprintf('Missing string to translate: %s', $source));
    }

    // Backup existing translation.
    $translation = $locale_storage->findTranslation(['lid' => $source->getId(), 'language' => $language->getId()]);
    $this->translations[$source->getId()] = [
      'language' => $language,
      'translation' => clone $translation,
    ];

    $new_translation = $translation->isTranslation() ? $translation : $locale_storage->createTranslation($source->getValues(['lid']) + ['language' => $language->getId()]);
    $new_translation->setString($value);
    $new_translation->save();
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
   * Check that we have the correct language for initial translation.
   *
   * @param string $title
   *   Title of node.
   *
   * @throws \Exception
   *   Throws an exception if:
   *    a)the node doesn't exist
   *    b) the node has more than one translation.
   *    c) the language of the translation is not the default site language.
   *
   * @Then The only available translation for :title is in the site's default language
   */
  public function assertOnlyDefaultLanguageTranslationExist(string $title): void {
    $node = $this->getEntityByLabel('node', $title);
    if (!$node) {
      throw new \RuntimeException("Node '{$title}' doesn't exist.");
    }

    $node_translation_languages = $node->getTranslationLanguages();
    if (count($node_translation_languages) !== 1) {
      throw new \RuntimeException("The node should have only one translation.");
    }

    $node_language = key($node_translation_languages);
    if ($node_language != \Drupal::languageManager()->getDefaultLanguage()->getId()) {
      throw new \RuntimeException("Original translation language of the '{$title}' node is not the site's default language.");
    }
  }

  /**
   * Assert that visitor is redirected to language selection page.
   *
   * @Then I should be redirected to the language selection page
   */
  public function assertLanguageSelectionPageRedirect() {
    $this->assertSession()->addressMatches("/.*\/select-language/");
  }

  /**
   * Assert links in region.
   *
   * @param \Behat\Gherkin\Node\TableNode $links
   *   List of links.
   *
   * @Then I should see the following links in the language switcher:
   */
  public function assertLinksInRegion(TableNode $links): void {
    $switcher_links = $this->getSession()->getPage()->findAll('css', '#block-oe-multilingual-language-switcher a');
    $actual_links = [];
    /** @var \Behat\Mink\Element\NodeElement $switcher_link */
    foreach ($switcher_links as $switcher_link) {
      $actual_links[] = $switcher_link->getText();
    }
    $expected_links = array_keys($links->getRowsHash());
    Assert::assertEquals($expected_links, $actual_links);
  }

}
