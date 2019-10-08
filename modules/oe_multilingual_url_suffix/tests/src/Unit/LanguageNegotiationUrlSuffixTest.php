<?php

namespace Drupal\Tests\oe_multilingual_url_suffix\Unit;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix
 * @group language
 */
class LanguageNegotiationUrlSuffixTest extends UnitTestCase {

  /**
   * The Language Manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    // Set up some languages to be used by the language-based path processor.
    $language_de = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language_de->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('de'));
    $language_en = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language_en->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $languages = [
      'de' => $language_de,
      'en' => $language_en,
    ];
    $this->languages = $languages;

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue($languages));
    $this->languageManager = $language_manager;

    // Create a user stub.
    $this->user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();

    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Test url suffix language negotiation and outbound path processing.
   *
   * @dataProvider providerTestPathSuffix
   */
  public function testPathSuffix($suffix, $suffixes, $expected_langcode) {
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($this->languages[(in_array($expected_langcode, ['en', 'de'])) ? $expected_langcode : 'en']));

    $config = $this->getConfigFactoryStub([
      'oe_multilingual_url_suffix.settings' => [
        'url_suffixes' => $suffixes,
      ],
    ]);

    $request = Request::create('/foo_' . $suffix, 'GET');
    $method = new LanguageNegotiationUrlSuffix();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $this->assertEquals($expected_langcode, $method->getLangcode($request));

    $cacheability = new BubbleableMetadata();
    $options = [];
    $method->processOutbound('foo', $options, $request, $cacheability);
    $expected_cacheability = new BubbleableMetadata();
    if ($expected_langcode) {
      $this->assertSame($expected_langcode, $options['language']->getId());
      $expected_cacheability->setCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
    }
    else {
      $this->assertFalse(empty($options['language']));
    }
    $this->assertEquals($expected_cacheability, $cacheability);
  }

  /**
   * Provides data for the url suffix test.
   *
   * @return array
   *   An array of data for checking path suffix negotiation.
   */
  public function providerTestPathSuffix() {
    $url_suffix_configuration[] = [
      'suffix' => 'de',
      'suffixes' => [
        'de' => 'de',
        'en-uk' => 'en',
      ],
      'expected_langcode' => 'de',
    ];
    $url_suffix_configuration[] = [
      'suffix' => 'en-uk',
      'suffixes' => [
        'de' => 'de',
        'en' => 'en-uk',
      ],
      'expected_langcode' => 'en',
    ];
    // No configuration.
    $url_suffix_configuration[] = [
      'suffix' => 'de',
      'suffixes' => [],
      'expected_langcode' => NULL,
    ];
    // Non-matching suffix.
    $url_suffix_configuration[] = [
      'suffix' => 'de',
      'suffixes' => [
        'en-uk' => 'en',
      ],
      'expected_langcode' => NULL,
    ];
    // Non-existing language.
    $url_suffix_configuration[] = [
      'suffix' => 'it',
      'suffixes' => [
        'it' => 'it',
        'en-uk' => 'en',
      ],
      'expected_langcode' => NULL,
    ];
    return $url_suffix_configuration;
  }

}
