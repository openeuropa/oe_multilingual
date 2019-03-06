<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\oe_multilingual\Plugin\LanguageNegotiation\LanguageNegotiationAdmin;

/**
 * Class InstallationTest.
 */
class InstallationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'content_translation',
    'locale',
    'language',
    'oe_multilingual',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('locale', [
      'locales_location',
      'locales_target',
      'locales_source',
      'locale_file',
    ]);

    $this->installConfig([
      'locale',
      'language',
      'content_translation',
      'oe_multilingual',
    ]);
    $this->container->get('module_handler')->loadInclude('oe_multilingual', 'install');
    oe_multilingual_install();
  }

  /**
   * Test languages keep the configuration after being deleted.
   */
  public function testLanguageConfiguration(): void {
    // Delete a language.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $language_manager */
    $entity_manager = $this->container->get('entity_type.manager');
    $storage = $entity_manager->getStorage('configurable_language');
    /** @var \Drupal\language\Entity\ConfigurableLanguage $old_language */
    $old_language = $storage->load('fr');
    $old_language->delete();

    // Assert language is deleted.
    $language = $storage->load('fr');
    $this->assertNull($language);

    // Create the same language.
    /** @var \Drupal\language\Entity\ConfigurableLanguage $new_language */
    $new_language = new ConfigurableLanguage(['id' => 'fr'], 'configurable_language');
    $new_language->save();
    $new_language = $storage->load('fr');

    $this->assertEquals($old_language->id(), $new_language->id());
    $this->assertEquals($old_language->getWeight(), $new_language->getWeight());
    $this->assertEquals($old_language->getName(), $new_language->getName());
  }

  /**
   * Test proper configuration setup during module installation.
   */
  public function testInstallation(): void {
    $config = $this->config('locale.settings');

    // Ensure that remote translations downloading is disabled by default.
    $this->assertEquals(FALSE, $config->get('translation.import_enabled'));

    // Ensure that the English language is translatable.
    $this->assertEquals(TRUE, $config->get('translate_english'));

    $interface_settings = [
      LanguageNegotiationAdmin::METHOD_ID => -20,
      LanguageNegotiationUrl::METHOD_ID => -19,
      LanguageNegotiationSelected::METHOD_ID => 20,
    ];
    $content_settings = [
      LanguageNegotiationUrl::METHOD_ID => -19,
      LanguageNegotiationSelected::METHOD_ID => 20,
    ];

    $config = $this->config('language.types');

    // Ensure that both interface and content negotiations are enabled.
    $this->assertEquals([
      LanguageInterface::TYPE_INTERFACE,
      LanguageInterface::TYPE_CONTENT,
    ], $config->get('configurable'));

    // Ensure that language negotiations are properly configured.
    $this->assertEquals($interface_settings, $config->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled'));
    $this->assertEquals($interface_settings, $config->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.method_weights'));
    $this->assertEquals($content_settings, $config->get('negotiation.' . LanguageInterface::TYPE_CONTENT . '.enabled'));
    $this->assertEquals($content_settings, $config->get('negotiation.' . LanguageInterface::TYPE_CONTENT . '.method_weights'));
  }

}
