<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
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

    $this->installSchema('user', ['users_data']);

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
   * Test languages keep the configuration after being deleted and recreated.
   */
  public function testLanguageConfiguration(): void {
    // Delete a language.
    $storage = $this->container->get('entity_type.manager')->getStorage('configurable_language');
    /** @var \Drupal\language\Entity\ConfigurableLanguage $old_language */
    $old_language = $storage->load('fr');
    $old_language->delete();

    // Assert the language is deleted.
    $language = $storage->load('fr');
    $this->assertNull($language);

    // Create the same language.
    /** @var \Drupal\language\ConfigurableLanguageInterface $new_language */
    $new_language = $storage->create(['id' => 'fr']);
    $new_language->save();
    $new_language = $storage->load('fr');

    // Assert the same values apply.
    $this->assertEquals($old_language->id(), $new_language->id());
    $this->assertEquals($old_language->getWeight(), $new_language->getWeight());
    $this->assertEquals($old_language->getName(), $new_language->getName());

    // Ensure the correct translation is also present.
    $translation = $this->container->get('language_manager')->getLanguageConfigOverride('fr', 'language.entity.fr');
    $this->assertEquals('français', $translation->get('label'));
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

  /**
   * Tests that uninstalling the module leaves the site in working condition.
   *
   * There can be plugins defined by the module which could no longer be found
   * otherwise.
   */
  public function testUninstall() {
    $this->container->get('module_installer')->uninstall(['oe_multilingual']);

    $config = $this->config('language.types');
    $interface_settings = [
      LanguageNegotiationUrl::METHOD_ID => -19,
      LanguageNegotiationSelected::METHOD_ID => 20,
    ];
    $this->assertEquals($interface_settings, $config->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled'));
    $this->assertEquals($interface_settings, $config->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.method_weights'));
  }

}
