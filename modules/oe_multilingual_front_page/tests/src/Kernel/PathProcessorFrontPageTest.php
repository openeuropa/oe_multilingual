<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual_front_page\Unit;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\token\Kernel\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

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
  public static $modules = [
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
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $node = $this->drupalCreateNode(['type' => 'article', 'title' => 'Test page']);
    // Set the node as front page.
    $this->config('system.site')->set('page.front', '/node/' . $node->id())->save();
    // Set node alias.
    \Drupal::service('path.alias_storage')->save($node->toUrl()->toString(), '/test-page', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $node_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = \Drupal::service('path.alias_manager')->getAliasByPath($front_uri);
    $this->assertEquals($front_alias, '/test-page');

    // Update node alias.
    $node_alias = \Drupal::service('path.alias_storage')->load(['alias' => $node_alias]);
    \Drupal::service('path.alias_storage')->save($front_uri, '/new-alias', LanguageInterface::LANGCODE_NOT_SPECIFIED, $node_alias['pid']);
    $node_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = \Drupal::service('path.alias_manager')->getAliasByPath($front_uri);
    $this->assertEquals($front_alias, '/new-alias');

    // Remove node alias.
    \Drupal::service('path.alias_storage')->delete(['alias' => $node_alias]);
    $node_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = \Drupal::service('path.alias_manager')->getAliasByPath($front_uri);
    $this->assertEquals($node_alias, $front_alias);

    // Set a different front page.
    $this->config('system.site')->set('page.front', '/user')->save();
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = \Drupal::service('path.alias_manager')->getAliasByPath($front_uri);
    $this->assertNotEqual($front_alias, $node_alias);

    // New node translatable node.
    $node = $this->drupalCreateNode(['type' => 'oe_demo_translatable_page', 'title' => 'Translatable page']);
    $node->addTranslation('fr', ['title' => 'Translatable page fr']);
    \Drupal::service('path.alias_storage')->save($node->toUrl()->toString(), '/translatable-page', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->config('system.site')->set('page.front', '/node/' . $node->id())->save();
    $front_uri = \Drupal::config('system.site')->get('page.front');
    $front_alias = \Drupal::service('path.alias_manager')->getAliasByPath($front_uri);
    $this->assertEquals('/translatable-page', $front_alias);
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'fr')->save();
    $this->assertEquals('/translatable-page', $front_alias);

  }

}
