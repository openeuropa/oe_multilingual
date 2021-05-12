<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\oe_multilingual\Plugin\LanguageNegotiation\LanguageNegotiationAdmin;

/**
 * Tests the multilingual installation.
 */
class InstallationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
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
    oe_multilingual_install(FALSE);
  }

  /**
   * Test proper configuration setup during module installation.
   */
  public function testInstallation(): void {
    $config = $this->config('locale.settings');

    // Ensure that remote translations downloading is disabled by default.
    $this->assertEquals(FALSE, $config->get('translation.import_enabled'));

    // Ensure that the English language is not translatable.
    $this->assertEquals(FALSE, $config->get('translate_english'));

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
