<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual_url_suffix\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix;
use Drupal\oe_multilingual_url_suffix_test\EventSubscriber\TestUrlSuffixesAlterEventSubscriber;
use Drupal\Tests\language\Kernel\LanguageTestBase;

/**
 * Tests the suffix-based language negotiation in entity URLs.
 *
 * @group language
 */
class EntityUrlSuffixLanguageTest extends LanguageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'entity_test',
    'language',
    'user',
    'oe_multilingual',
    'oe_multilingual_url_suffix',
    'oe_multilingual_url_suffix_test',
  ];

  /**
   * The entity being used for testing.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('configurable_language');
    $this->installConfig([
      'language',
      'oe_multilingual',
      'oe_multilingual_url_suffix',
    ]);
    \Drupal::service('router.builder')->rebuild();

    \Drupal::service('kernel')->rebuildContainer();

    $config = $this->config('language.types');
    $config->set('configurable', [LanguageInterface::TYPE_INTERFACE]);
    $config->set('negotiation.language_interface.enabled', [
      LanguageNegotiationUrlSuffix::METHOD_ID => 0,
    ]);
    $config->save();
    $this->config('system.site')->set('page.front', '/')->save();

    $this->createTranslatableEntity();
  }

  /**
   * Ensures that entity URLs have the right language suffix.
   */
  public function testEntityUrlLanguage(): void {
    $this->assertTrue(strpos($this->entity->toUrl()->toString(), '/entity_test/' . $this->entity->id() . '_en') !== FALSE);
    $this->assertTrue(strpos($this->entity->getTranslation('es')->toUrl()->toString(), '/entity_test/' . $this->entity->id() . '_es') !== FALSE);
    $this->assertTrue(strpos($this->entity->getTranslation('fr')->toUrl()->toString(), '/entity_test/' . $this->entity->id() . '_fr') !== FALSE);

    // Set the state to trigger our test event subscriber.
    $this->container->get('state')->set(TestUrlSuffixesAlterEventSubscriber::BLACKLISTED_SUFFIXES, ['en']);
    // Assert that the '_en' is not found, because of our test event subscriber.
    // @see: TestUrlSuffixesAlterEventSubscriber::alterUrlSuffixes().
    $this->assertTrue(strpos($this->entity->toUrl()->toString(), '/entity_test/' . $this->entity->id() . '_en') === FALSE);
    // Assert that the '_en' is found, because in our test event subscriber used
    // path matching from context.
    // @see: TestUrlSuffixesAlterEventSubscriber::alterUrlSuffixes().
    $this->container->get('state')->set(TestUrlSuffixesAlterEventSubscriber::WHITELISTED_PATHS, ['/entity_test/*']);
    $this->assertTrue(strpos($this->entity->toUrl()->toString(), '/entity_test/' . $this->entity->id() . '_en') !== FALSE);
  }

  /**
   * Creates a translated entity.
   */
  protected function createTranslatableEntity(): void {
    $this->entity = EntityTest::create();
    $this->entity->addTranslation('es', ['name' => 'name spanish']);
    $this->entity->addTranslation('fr', ['name' => 'name french']);
    $this->entity->save();
  }

}
