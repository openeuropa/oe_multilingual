<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual_front_page\Unit;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Core\Url;

/**
 * @coversDefaultClass \Drupal\oe_multilingual_front_page\PathProcessorFrontPage
 * @group language
 */
class PathProcessorFrontPageTest extends KernelTestBase {

  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'node',
    'system',
    'path',
    'field',
    'filter',
    'text',
    'oe_multilingual_front_page',
    'language',
    'content_translation',
    'token',
    'user',
  ];

  /**
   * A config for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'node', 'filter']);

    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Test basic outbound processing functionality.
   *
   * @covers ::processOutbound
   */
  public function testFrontPagePath() {
    $alias_storage = $this->container->get('path.alias_storage');
    $alias_manager = $this->container->get('path.alias_manager');

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $node = $this->drupalCreateNode(['type' => 'article', 'title' => 'Test page']);
    // Set the node as front page.
    $this->config('system.site')->set('page.front', '/node/' . $node->id())->save();
    // Set node alias.
    $alias_storage->save($node->toUrl()->toString(), '/test-page', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $node_alias = $alias_manager->getAliasByPath('/node/' . $node->id());
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = $alias_manager->getAliasByPath($front_uri);
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals($front_alias, '/test-page');
    $this->assertEquals($front_alias, $url);

    // Update node alias.
    $node_alias = \Drupal::service('path.alias_storage')->load(['alias' => $node_alias]);
    $alias_storage->save($front_uri, '/new-alias', LanguageInterface::LANGCODE_NOT_SPECIFIED, $node_alias['pid']);
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = $alias_manager->getAliasByPath($front_uri);
    // Check that the front page alias updates.
    $this->assertEquals($front_alias, '/new-alias');
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals($front_alias, $url);

    // Remove node alias.
    $alias_storage->delete($node_alias);
    $node_alias = $alias_manager->getAliasByPath('/node/' . $node->id());
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = $alias_manager->getAliasByPath($front_uri);
    // Check that the front page alias updates.
    $this->assertEquals($front_alias, $node_alias);
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals($front_alias, $url);

    // New node translatable node.
    $node = $this->drupalCreateNode(['type' => 'oe_demo_translatable_page', 'title' => 'Translatable page']);
    $node->addTranslation('fr', ['title' => 'Translatable page fr'])->save();
    $alias_storage->save($node->toUrl()->toString(), '/translatable-page', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->config('system.site')->set('page.front', '/node/' . $node->id())->save();
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = $alias_manager->getAliasByPath($front_uri);
    $this->assertEquals('/translatable-page', $front_alias);
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals($front_alias, $url);
    // Set the default language to French.
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'fr')->save();
    // Check that the alias is the same.
    $this->assertEquals('/translatable-page', $front_alias);
    $this->assertEquals($front_alias, $url);
  }

}
