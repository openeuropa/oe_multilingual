<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_multilingual_url_suffix\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\oe_multilingual_url_suffix\Hook\OeMultilingualUrlSuffixHooks;
use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the redirect response alter hook.
 *
 * @group oe_multilingual_url_suffix
 */
class RedirectResponseAlterTest extends UnitTestCase {

  /**
   * The language manager mock.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $languageManager;

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The hooks service being tested.
   *
   * @var \Drupal\oe_multilingual_url_suffix\Hook\OeMultilingualUrlSuffixHooks
   */
  protected $hooksService;

  /**
   * Mock languages.
   *
   * @var array
   */
  protected $languages = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $english = $this->prophesize(LanguageInterface::class);
    $english->getId()->willReturn('en');
    $this->languages['en'] = $english->reveal();

    $french = $this->prophesize(LanguageInterface::class);
    $french->getId()->willReturn('fr');
    $this->languages['fr'] = $french->reveal();

    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->languageManager->getLanguage('en')->willReturn($this->languages['en']);
    $this->languageManager->getLanguage('fr')->willReturn($this->languages['fr']);
    $this->languageManager->getLanguage(Language::LANGCODE_NOT_SPECIFIED)->willReturn(NULL);

    $suffixConfig = $this->prophesize(ImmutableConfig::class);
    $suffixConfig->get('url_suffixes')->willReturn([
      'en' => 'en',
      'fr' => 'fr',
    ]);

    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->configFactory->get('oe_multilingual_url_suffix.settings')->willReturn($suffixConfig->reveal());

    $this->hooksService = new OeMultilingualUrlSuffixHooks(
      $this->languageManager->reveal(),
      $this->configFactory->reveal()
    );
  }

  /**
   * Tests hook skips redirects without specific language or suffix.
   */
  public function testHookSkipsUnspecifiedLanguageWithoutSuffix(): void {
    $redirectLanguage = $this->prophesize(LanguageInterface::class);
    $redirectLanguage->getId()->willReturn(Language::LANGCODE_NOT_SPECIFIED);

    $fieldItemList = new \stdClass();
    $fieldItemList->uri = 'internal:/test-page';

    $redirect = $this->prophesize(Redirect::class);
    $redirect->language()->willReturn($redirectLanguage->reveal());
    $redirect->get('redirect_redirect')->willReturn($fieldItemList);

    $originalUrl = 'http://example.com/test-page_en';
    $response = new TrustedRedirectResponse($originalUrl);

    $this->hooksService->redirectResponseAlter($response, $redirect->reveal());

    $this->assertEquals($originalUrl, $response->getTargetUrl());
  }

  /**
   * Tests hook regenerates URL when redirect language differs from current.
   */
  public function testHookRegeneratesUrlWhenLanguageDiffers(): void {
    $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)
      ->willReturn($this->languages['en']);

    $redirectLanguage = $this->prophesize(LanguageInterface::class);
    $redirectLanguage->getId()->willReturn('fr');

    $fieldItemList = new \stdClass();
    $fieldItemList->uri = 'internal:/node/1';

    $url = $this->prophesize(Url::class);
    $url->setOption('language', $this->languages['fr'])->willReturn($url->reveal());
    $url->setAbsolute()->willReturn($url->reveal());
    $url->toString()->willReturn('http://example.com/test-page_fr');

    $redirect = $this->prophesize(Redirect::class);
    $redirect->language()->willReturn($redirectLanguage->reveal());
    $redirect->get('redirect_redirect')->willReturn($fieldItemList);
    $redirect->getRedirectUrl()->willReturn($url->reveal());

    $response = new TrustedRedirectResponse('http://example.com/test-page_en');

    $this->hooksService->redirectResponseAlter($response, $redirect->reveal());

    $this->assertEquals('http://example.com/test-page_fr', $response->getTargetUrl());
  }

  /**
   * Tests hook detects language suffix in redirect destination path.
   */
  public function testHookDetectsDestinationPathSuffix(): void {
    $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)
      ->willReturn($this->languages['en']);

    $redirectLanguage = $this->prophesize(LanguageInterface::class);
    $redirectLanguage->getId()->willReturn(Language::LANGCODE_NOT_SPECIFIED);

    $fieldItemList = new \stdClass();
    $fieldItemList->uri = 'internal:/test-page_fr';

    $url = $this->prophesize(Url::class);
    $url->setOption('language', $this->languages['fr'])->willReturn($url->reveal());
    $url->setAbsolute()->willReturn($url->reveal());
    $url->toString()->willReturn('http://example.com/test-page_fr');

    $redirect = $this->prophesize(Redirect::class);
    $redirect->language()->willReturn($redirectLanguage->reveal());
    $redirect->get('redirect_redirect')->willReturn($fieldItemList);
    $redirect->getRedirectUrl()->willReturn($url->reveal());

    $response = new TrustedRedirectResponse('http://example.com/test-page_en');

    $this->hooksService->redirectResponseAlter($response, $redirect->reveal());

    $this->assertEquals('http://example.com/test-page_fr', $response->getTargetUrl());
  }

}
