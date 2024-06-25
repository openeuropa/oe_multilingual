<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_multilingual_url_suffix\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the suffix based language negotiation method.
 *
 * @coversDefaultClass \Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix
 * @group language
 */
class LanguageNegotiationUrlSuffixTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'node',
    'path',
    'oe_multilingual_url_suffix',
    'content_translation',
  ];

  /**
   * The logged-in user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article']);

      $this->container->get('content_translation.manager')->setEnabled('node', 'article', TRUE);
      $this->container->get('router.builder')->rebuild();
    }

    $this->user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'view the administration theme',
      'administer nodes',
      'create article content',
      'create url aliases',
    ]);
    $this->drupalLogin($this->user);

    // Enable URL language detection and selection.
    $edit = [
      'language_interface[enabled][oe-multilingual-url-suffix-negotiation-method]' => 1,
      'language_interface[enabled][language-url]' => 0,
      'language_content[enabled][oe-multilingual-url-suffix-negotiation-method]' => 1,
      'language_content[enabled][language-url]' => 0,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, t('Save settings'));
  }

  /**
   * Tests that inbound requests are able to be correctly negotiated.
   *
   * @covers ::processInbound
   */
  public function testInbound(): void {
    // Check if paths that contain language suffix can be reached when
    // language is taken from the url suffix.
    $edit = [
      'suffix[en]' => 'eng',
    ];
    $this->drupalGet('/admin/config/regional/language/detection/url-suffix_en', ['external' => FALSE]);
    $this->submitForm($edit, $this->t('Save configuration'));

    $nodeValues = [
      'title[0][value]' => 'Test',
      'path[0][alias]' => '/test_eng',
    ];
    $this->drupalGet('/node/add/article_eng', ['external' => FALSE]);
    $this->submitForm($nodeValues, $this->t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/test_eng_eng', ['external' => FALSE]);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/test_eng');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that we can force the negotiator to check for entity translation.
   */
  public function testEntityTranslationCheck(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'My node',
    ]);
    $node->addTranslation('fr', ['title' => 'My FR node']);
    $node->save();

    $this->drupalGet('/node/' . $node->id() . '_en', ['external' => FALSE]);
    $this->assertSession()->pageTextContains('My node');
    $this->assertSession()->elementAttributeContains('css', 'html', 'lang', 'en');
    $this->drupalGet('/node/' . $node->id() . '_fr', ['external' => FALSE]);
    $this->assertSession()->pageTextContains('My FR node');
    $this->assertSession()->elementAttributeContains('css', 'html', 'lang', 'fr');
    $this->drupalGet('/node/' . $node->id() . '_de', ['external' => FALSE]);
    $this->assertSession()->pageTextContains('My node');
    // Even though there is no DE translation, the DE language gets negotiated
    // so the html attribute language is set as DE.
    $this->assertSession()->elementAttributeContains('css', 'html', 'lang', 'de');

    $this->drupalGet('/admin/config/regional/language/detection/url-suffix_en', ['external' => FALSE]);
    $this->getSession()->getPage()->checkField('Check entity translation');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('/node/' . $node->id() . '_en', ['external' => FALSE]);
    $this->assertSession()->elementAttributeContains('css', 'html', 'lang', 'en');
    $this->assertSession()->pageTextContains('My node');
    $this->drupalGet('/node/' . $node->id() . '_fr', ['external' => FALSE]);
    $this->assertSession()->elementAttributeContains('css', 'html', 'lang', 'fr');
    $this->assertSession()->pageTextContains('My FR node');
    $this->drupalGet('/node/' . $node->id() . '_de', ['external' => FALSE]);
    $this->assertSession()->pageTextContains('My node');
    // The DE language no longer gets negotiated because the node doesn't have
    // a translation in DE so the page lang falls back to EN.
    $this->assertSession()->elementAttributeContains('css', 'html', 'lang', 'en');

  }

}
