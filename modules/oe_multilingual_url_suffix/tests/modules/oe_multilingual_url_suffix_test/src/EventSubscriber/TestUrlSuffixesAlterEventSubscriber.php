<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual_url_suffix_test\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\State\State;
use Drupal\oe_multilingual_url_suffix\Event\UrlSuffixesAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber for the url suffixes alter event.
 */
class TestUrlSuffixesAlterEventSubscriber implements EventSubscriberInterface {

  const STATE = 'oe_multilingual_url_suffix_test.test_alter_url_suffixes';

  /**
   * The state.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * TestUrlSuffixesAlterEventSubscriber constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The state.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(State $state, ConfigFactoryInterface $config_factory, PathMatcherInterface $path_matcher) {
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      UrlSuffixesAlterEvent::EVENT => 'alterUrlSuffixes',
    ];
  }

  /**
   * Alters list of url suffixes by removing the 'en' key from the list.
   *
   * @param \Drupal\oe_multilingual_url_suffix\Event\UrlSuffixesAlterEvent $event
   *   The event.
   */
  public function alterUrlSuffixes(UrlSuffixesAlterEvent $event): void {
    if (!$blacklist = $this->state->get(static::STATE)) {
      return;
    }

    if (!is_array($blacklist)) {
      return;
    }
    // Exclude some paths from processing in this event subscriber.
    $whitelisted_paths = $this->configFactory->get('oe_multilingual_url_suffix.settings')->get('whitelisted_paths') ?? [];
    if (!$event->getPath()) {
      return;
    }
    foreach ($whitelisted_paths as $whitelisted_path) {
      if ($event->getPath() && $this->pathMatcher->matchPath($event->getPath(), $whitelisted_path)) {
        return;
      }
    }

    $suffixes = $event->getUrlSuffixes();
    $event->setUrlSuffixes(array_diff($suffixes, $blacklist));
  }

}
