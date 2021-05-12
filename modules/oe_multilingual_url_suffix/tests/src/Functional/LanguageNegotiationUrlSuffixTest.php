<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual_url_suffix\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
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
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article']);
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
    $edit = ['language_interface[enabled][oe-multilingual-url-suffix-negotiation-method]' => 1];
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
    $this->drupalGet('admin/config/regional/language/detection/url-suffix_en');
    $this->submitForm($edit, $this->t('Save configuration'));

    $nodeValues = [
      'title[0][value]' => 'Test',
      'path[0][alias]' => '/test_eng',
    ];
    $this->drupalGet('node/add/article_eng');
    $this->submitForm($nodeValues, $this->t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/test_eng_eng');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/test_eng');
    $this->assertSession()->statusCodeEquals(404);

  }

}
