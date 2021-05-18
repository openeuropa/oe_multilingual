<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests features implemented for the configurable language config entity.
 */
class ConfigurableLanguageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'administer languages',
      'access administration pages',
      'view the administration theme',
    ];
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->createUser($permissions, 'test');
    $this->drupalLogin($user);
  }

  /**
   * Tests the language category setting.
   */
  public function testLanguageCategorySetting(): void {
    // Check that the languages shipped by the module
    // have EU category by default.
    $languages = \Drupal::languageManager()->getLanguages();
    $configurable_languages = \Drupal::entityTypeManager()->getStorage('configurable_language')->loadMultiple(array_keys($languages));
    /** @var \Drupal\language\Entity\ConfigurableLanguage $language */
    foreach ($configurable_languages as $language) {
      $this->assertEquals('eu', $language->getThirdpartySetting('oe_multilingual', 'category'));
    }

    // Test the category select in the English language setting form.
    $this->drupalGet('admin/config/regional/language/edit/en');
    $this->assertSession()->optionExists('Category', 'EU')->hasAttribute('selected');
    $this->assertSession()->optionExists('Category', 'Non-EU');

    // Select Non-EU option as category and save.
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('Category', 'Non-EU');
    $page->pressButton('Save language');

    // Reload the page and assert the Non-EU option is selected.
    $this->drupalGet('admin/config/regional/language/edit/en');
    $this->assertSession()->optionExists('Category', 'Non-EU')->hasAttribute('selected');
  }

}
