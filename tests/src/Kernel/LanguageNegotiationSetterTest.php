<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the language negotiation setter.
 */
class LanguageNegotiationSetterTest extends KernelTestBase {

  /**
   * Service under test.
   *
   * @var \Drupal\oe_multilingual\LanguageNegotiationSetterInterface
   */
  private $service;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
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
      'oe_multilingual',
    ]);

    $this->service = $this->container->get('oe_multilingual.language_negotiation_setter');
  }

  /**
   * Test setting language negotiation settings.
   */
  public function testSetSettings(): void {
    $this->service->setInterfaceSettings([
      'foo' => -20,
      'bar' => -19,
    ]);
    $this->service->setContentSettings([
      'bar' => -20,
      'foo' => -19,
    ]);

    $settings = $this
      ->config('language.types')
      ->get('negotiation.language_interface');

    $this->assertEquals([
      'enabled' => [
        'foo' => -20,
        'bar' => -19,
      ],
      'method_weights' => [
        'foo' => -20,
        'bar' => -19,
      ],
    ], $settings);

    $settings = $this
      ->config('language.types')
      ->get('negotiation.language_content');

    $this->assertEquals([
      'enabled' => [
        'bar' => -20,
        'foo' => -19,
      ],
      'method_weights' => [
        'bar' => -20,
        'foo' => -19,
      ],
    ], $settings);
  }

  /**
   * Test adding language negotiation settings to existing ones.
   */
  public function testAddSettings(): void {
    $this->service->setInterfaceSettings([
      'foo' => -20,
      'bar' => -18,
    ]);
    $this->service->setContentSettings([
      'foo' => -20,
      'bar' => -18,
    ]);

    $this->service->addInterfaceSettings(['boo' => -19]);
    $this->service->addContentSettings(['boo' => -19]);

    foreach (['language_interface', 'language_content'] as $type) {
      $settings = $this
        ->config('language.types')
        ->get('negotiation.' . $type);

      $this->assertEquals([
        'enabled' => [
          'foo' => -20,
          'boo' => -19,
          'bar' => -18,
        ],
        'method_weights' => [
          'foo' => -20,
          'boo' => -19,
          'bar' => -18,
        ],
      ], $settings);
    }
  }

}
