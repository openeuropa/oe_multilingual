<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the translator importer service using a test Controller.
 */
class TranslationImporterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_multilingual',
    'translation_importer_test',
  ];

  /**
   * Tests the translation importer.
   */
  public function testTranslationImportBatch(): void {
    $locale_storage = $this->container->get('locale.storage');
    $translations = $locale_storage->getTranslations(['source' => 'Structure']);
    $this->assertEmpty($translations);
    $this->drupalGet('import-translations');
    // Wait for the AJAX to complete. This can take a while until the batch
    // process finishes hence the increase in timeout.
    $this->assertSession()->assertWaitOnAjaxRequest(100000);
    $this->assertSession()->pageTextContains('The batch has completed');
    $translations = $locale_storage->getTranslations(['source' => 'Structure']);
    $this->assertCount(1, $translations);
    $translation = reset($translations);
    $this->assertEquals('French STR', $translation->translation);
  }

}
