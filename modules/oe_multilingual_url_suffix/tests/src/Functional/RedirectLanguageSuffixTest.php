<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_multilingual_url_suffix\Functional;

use Drupal\Core\Language\Language;
use Drupal\node\Entity\Node;
use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests redirect integration with URL suffix language negotiation.
 *
 * @group oe_multilingual_url_suffix
 */
class RedirectLanguageSuffixTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'node',
    'path',
    'path_alias',
    'redirect',
    'oe_multilingual_url_suffix',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test node with translations.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an Article node type.
    $this->drupalCreateContentType(['type' => 'article']);

    // Enable content translation for articles.
    $this->container->get('content_translation.manager')->setEnabled('node', 'article', TRUE);
    $this->container->get('router.builder')->rebuild();

    // Create a user with necessary permissions.
    $user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer redirects',
      'administer nodes',
      'create article content',
      'edit any article content',
      'create url aliases',
      'translate any entity',
      'create content translations',
    ]);
    $this->drupalLogin($user);

    // Enable URL suffix language detection.
    $edit = [
      'language_interface[enabled][oe-multilingual-url-suffix-negotiation-method]' => 1,
      'language_interface[enabled][language-url]' => 0,
      'language_content[enabled][oe-multilingual-url-suffix-negotiation-method]' => 1,
      'language_content[enabled][language-url]' => 0,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Create a node with English and French translations.
    $this->node = Node::create([
      'type' => 'article',
      'title' => 'English Title',
      'langcode' => 'en',
      'path' => ['alias' => '/test-page'],
    ]);
    $this->node->save();

    $this->node->addTranslation('fr', [
      'title' => 'French Title',
      'path' => ['alias' => '/test-page'],
    ]);
    $this->node->save();
  }

  /**
   * Tests redirect with path containing language suffix in destination.
   */
  public function testRedirectWithPathSuffixDestination(): void {
    // Create a redirect using a path with French suffix as destination.
    $redirect = Redirect::create([
      'redirect_source' => 'my-redirect',
      'redirect_redirect' => 'internal:/test-page_fr',
      'language' => Language::LANGCODE_NOT_SPECIFIED,
      'status_code' => 301,
    ]);
    $redirect->save();

    // Visit without language suffix - should still redirect to French.
    // Use 'external' => FALSE to skip client-side path processing, as the
    // test process's PathProcessorLanguage may have stale processors cached
    // from before the language negotiation configuration was changed.
    $this->drupalGet('/my-redirect', ['external' => FALSE]);
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('_fr', $current_url, 'Redirect should preserve French suffix from destination path.');
    $this->assertSession()->pageTextContains('French Title');
  }

  /**
   * Tests redirect with entity reference uses current request language.
   */
  public function testRedirectWithEntityReference(): void {
    // Create a redirect to an entity, without specific language.
    $redirect = Redirect::create([
      'redirect_source' => 'entity-redirect',
      'redirect_redirect' => 'internal:/node/' . $this->node->id(),
      'language' => Language::LANGCODE_NOT_SPECIFIED,
      'status_code' => 301,
    ]);
    $redirect->save();

    // Visit without language suffix, current language defaults to English.
    $this->drupalGet('/entity-redirect', ['external' => FALSE]);
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('_en', $current_url, 'Entity redirect should use current language (English).');
    $this->assertSession()->pageTextContains('English Title');

    // Visit with French suffix, should redirect to French.
    $this->drupalGet('/entity-redirect_fr', ['external' => FALSE]);
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('_fr', $current_url, 'Entity redirect should use request language (French).');
    $this->assertSession()->pageTextContains('French Title');
  }

  /**
   * Tests that redirect Language field controls when redirect matches.
   */
  public function testRedirectLanguageFieldControlsMatching(): void {
    // Create a redirect that only matches French requests.
    $redirect = Redirect::create([
      'redirect_source' => 'french-only',
      'redirect_redirect' => 'internal:/node/' . $this->node->id(),
      'language' => 'fr',
      'status_code' => 301,
    ]);
    $redirect->save();

    // Visit without French suffix, redirect should NOT match.
    $this->drupalGet('/french-only', ['external' => FALSE]);
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('french-only', $current_url, 'Redirect should not match non-French request.');

    // Visit with French suffix, redirect should match.
    $this->drupalGet('/french-only_fr', ['external' => FALSE]);
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringNotContainsString('french-only', $current_url, 'Redirect should match French request.');
    $this->assertSession()->pageTextContains('French Title');
  }

}
