<?php

declare(strict_types=1);

namespace Drupal\oe_multilingual_url_suffix_test\EventSubscriber;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\State\State;
use Drupal\oe_multilingual_url_suffix\Event\UrlSuffixesAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber for the url suffixes alter event.
 */
class TestUrlSuffixesAlterEventSubscriber implements EventSubscriberInterface {

  const BLACKLISTED_SUFFIXES = 'oe_multilingual_url_suffix_test.blacklisted_url_suffixes';

  const WHITELISTED_PATHS = 'oe_multilingual_url_suffix_test.whitelisted_paths';

  /**
   * The state.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

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
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(State $state, PathMatcherInterface $path_matcher) {
    $this->state = $state;
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
    if (!$blacklist = $this->state->get(static::BLACKLISTED_SUFFIXES)) {
      return;
    }

    if (!is_array($blacklist)) {
      return;
    }
    // Exclude some paths from processing in this event subscriber.
    $whitelisted_paths = $this->state->get(static::WHITELISTED_PATHS) ?? [];
    if (!$event->getContext() || !$event->getContext()['path']) {
      return;
    }
    foreach ($whitelisted_paths as $whitelisted_path) {
      if ($this->pathMatcher->matchPath($event->getContext()['path'], $whitelisted_path)) {
        return;
      }
    }

    $suffixes = $event->getUrlSuffixes();
    $event->setUrlSuffixes(array_diff($suffixes, $blacklist));
  }

}
