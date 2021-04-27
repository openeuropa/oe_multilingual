<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual_front_page\Unit;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Core\Url;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

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

  use PathAliasTestTrait;

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
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installConfig(['system', 'node', 'filter']);

    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Test basic outbound processing functionality.
   *
   * @covers ::processOutbound
   */
  public function testFrontPagePath() {
    $alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $system_site_config = \Drupal::configFactory()->getEditable('system.site');

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test page',
    ]);
    // Set the node as front page.
    $system_site_config->set('page.front', '/node/' . $node->id())->save();
    // Set node alias.
    $this->createPathAlias($node->toUrl()->toString(), '/test-page');
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/test-page', $url);

    // Update node alias.
    $node_alias = $this->loadPathAliasByConditions(['alias' => '/test-page']);
    $node_alias->setAlias('/new-alias');
    $node_alias->save();
    // Check that the front page alias updates.
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/new-alias', $url);

    // Remove node alias.
    $node_alias->delete();
    // Check that the front page alias updates.
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/node/1', $url);

    // New node translatable node.
    $node = $this->drupalCreateNode([
      'type' => 'oe_demo_translatable_page',
      'title' => 'Translatable page',
    ]);
    $node->addTranslation('fr', ['title' => 'Translatable page fr'])->save();
    $this->createPathAlias($node->toUrl()->toString(), '/translatable-page');
    $system_site_config->set('page.front', '/node/' . $node->id())->save();
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/translatable-page', $url);
    // Set the default language to French.
    $system_site_config->set('default_langcode', 'fr')->save();
    // Check that the alias is the same, as alias are language independent.
    $this->assertEquals('/translatable-page', $url);
  }

}
