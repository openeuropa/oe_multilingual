<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual_url_suffix_test\EventSubscriber;

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
   * TestUrlSuffixesAlterEventSubscriber constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The state.
   */
  public function __construct(State $state) {
    $this->state = $state;
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

    $suffixes = $event->getUrlSuffixes();
    $event->setUrlSuffixes(array_diff($suffixes, $blacklist));
  }

}
