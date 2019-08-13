<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual_url_suffix\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\oe_multilingual\Plugin\LanguageNegotiation\LanguageNegotiationAdmin;
use Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix;
use Drupal\Tests\oe_multilingual\Kernel\InstallationTest;

/**
 * Class UrlSuffixInstallationTest.
 */
class UrlSuffixInstallationTest extends InstallationTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['oe_multilingual_url_suffix'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('module_handler')->loadInclude('oe_multilingual_url_suffix', 'install');
    oe_multilingual_url_suffix_install();
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
      LanguageNegotiationUrlSuffix::METHOD_ID => -19,
      LanguageNegotiationSelected::METHOD_ID => 20,
    ];
    $content_settings = [
      LanguageNegotiationUrlSuffix::METHOD_ID => -19,
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
    $this->container->get('module_installer')->uninstall(['oe_multilingual_url_suffix']);

    $config = $this->config('language.types');
    $interface_settings = [
      LanguageNegotiationAdmin::METHOD_ID => -20,
      LanguageNegotiationUrl::METHOD_ID => -19,
      LanguageNegotiationSelected::METHOD_ID => 20,
    ];
    $this->assertEquals($interface_settings, $config->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled'));
    $this->assertEquals($interface_settings, $config->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.method_weights'));
  }

}
