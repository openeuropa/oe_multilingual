<?php

declare(strict_types = 1);

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
   * The Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Test languages.
   *
   * @var array
   */
  protected $languages = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Set up some languages to be used by the language-based path processor.
    $language_de = $this->getMockBuilder('\Drupal\Core\Language\LanguageInterface')->getMock();
    $language_de->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('de'));
    $language_en = $this->getMockBuilder('\Drupal\Core\Language\LanguageInterface')->getMock();
    $language_en->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $this->languages = [
      'de' => $language_de,
      'en' => $language_en,
    ];

    // Create event dispatcher stub.
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue($this->languages));
    $this->languageManager = $language_manager;

    // Create a user stub.
    $this->user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')->getMock();

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
   * @param string $suffix
   *   The test suffix.
   * @param array $suffixes
   *   The configured suffixes.
   * @param string $expected_langcode
   *   The expected langcode.
   *
   * @dataProvider providerTestPathSuffix
   */
  public function testPathSuffix(string $suffix, array $suffixes, string $expected_langcode = NULL): void {
    $language_code = (in_array($expected_langcode, ['en', 'de'])) ? $expected_langcode : 'en';
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($this->languages[$language_code]));

    $config = $this->getConfigFactoryStub([
      'oe_multilingual_url_suffix.settings' => [
        'url_suffixes' => $suffixes,
      ],
    ]);

    $request = Request::create('/foo_' . $suffix, 'GET');
    $method = new LanguageNegotiationUrlSuffix($this->eventDispatcher);
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
   *   An array of data for checking the path suffix negotiation.
   */
  public function providerTestPathSuffix(): array {
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
