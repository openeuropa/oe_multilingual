<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Kernel;

use Drupal\administration_language_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationAdministrationLanguage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;

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
    'administration_language_negotiation',
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

    $this->installConfig([
      'locale',
      'language',
      'content_translation',
      'administration_language_negotiation',
      'oe_multilingual',
    ]);
    $this->container->get('module_handler')->loadInclude('oe_multilingual', 'install');
    oe_multilingual_install();
  }

  /**
   * Test proper configuration setup during module installation.
   */
  public function testInstallation():void {
    $config = $this->config('locale.settings');

    // Ensure that remote translations downloading is disabled by default.
    $this->assertEquals(FALSE, $config->get('translation.import_enabled'));

    // Ensure that the English language is translatable.
    $this->assertEquals(TRUE, $config->get('translate_english'));

    $config = $this->config('administration_language_negotiation.negotiation');

    // Ensure administration language on specific administrative paths.
    $this->assertEquals([
      '/admin',
      '/admin/*',
      '/node/add/*',
      '/node/*/edit',
      '/node/*/translations',
    ], $config->get('paths'));

    $interface_settings = [
      LanguageNegotiationAdministrationLanguage::METHOD_ID => -20,
      LanguageNegotiationUrl::METHOD_ID                    => -19,
      LanguageNegotiationSelected::METHOD_ID               => 20,
    ];
    $content_settings = [
      LanguageNegotiationUrl::METHOD_ID      => -19,
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
