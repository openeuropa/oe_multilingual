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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'node', 'filter']);

    // In Drupal 8.8, paths have been moved to an entity type.
    // @todo remove this when the component will depend on 8.8.
    if ($this->container->get('entity_type.manager')->hasDefinition('path_alias')) {
      $this->container->get('module_installer')->install(['path_alias']);
      $this->installEntitySchema('path_alias');
    }

    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Test basic outbound processing functionality.
   *
   * @covers ::processOutbound
   */
  public function testFrontPagePath() {
    $alias_storage = $this->container->get('path.alias_storage');
    $system_site_config = \Drupal::configFactory()->getEditable('system.site');

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $node = $this->drupalCreateNode(['type' => 'article', 'title' => 'Test page']);
    // Set the node as front page.
    $system_site_config->set('page.front', '/node/' . $node->id())->save();
    // Set node alias.
    $alias_storage->save($node->toUrl()->toString(), '/test-page', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/test-page', $url);

    // Update node alias.
    $node_alias = $alias_storage->load(['alias' => '/test-page']);
    $alias_storage->save('/node/' . $node->id(), '/new-alias', LanguageInterface::LANGCODE_NOT_SPECIFIED, $node_alias['pid']);
    // Check that the front page alias updates.
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/new-alias', $url);

    // Remove node alias.
    $alias_storage->delete(['alias' => '/new-alias']);
    // Check that the front page alias updates.
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/node/1', $url);

    // New node translatable node.
    $node = $this->drupalCreateNode(['type' => 'oe_demo_translatable_page', 'title' => 'Translatable page']);
    $node->addTranslation('fr', ['title' => 'Translatable page fr'])->save();
    $alias_storage->save($node->toUrl()->toString(), '/translatable-page', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $system_site_config->set('page.front', '/node/' . $node->id())->save();
    $url = Url::fromRoute('<front>')->toString();
    $this->assertEquals('/translatable-page', $url);
    // Set the default language to French.
    $system_site_config->set('default_langcode', 'fr')->save();
    // Check that the alias is the same, as alias are language independent.
    $this->assertEquals('/translatable-page', $url);
  }

}
