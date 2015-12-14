<?php

/**
 * @file
 * Contains \Drupal\entity\Controller\RevisionOverviewController.
 */

namespace Drupal\entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity\Revision\EntityRevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

class RevisionOverviewController extends ControllerBase {

  use RevisionControllerTrait;

  /**
   * Returns the date formatter.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected function dateFormatter() {
    return \Drupal::service('date.formatter');
  }

  /**
   * {@inheritdoc}
   */
  protected function hasDeleteRevisionAccess(EntityInterface $entity) {
    return $this->currentUser()->hasPermission("delete all {$entity->id()} revisions");
  }

  /**
   * {@inheritdoc}
   */
  protected function buildRevertRevisionLink(EntityInterface $entity_revision) {
    return [
      'title' => t('Revert'),
      'url' => $entity_revision->urlInfo('revision-revert'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDeleteRevisionLink(EntityInterface $entity_revision) {
    return [
      'title' => t('Delete'),
      'url' => $entity_revision->urlInfo('revision-delete'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRevisionDescription(ContentEntityInterface $revision, $is_current = FALSE) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\user\EntityOwnerInterface $revision */

    if ($revision instanceof EntityOwnerInterface) {
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getOwner(),
      ];
    }
    else {
      $username = '';
    }

    if ($revision instanceof EntityRevisionLogInterface) {
      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter()->format($revision->getRevisionCreationTime(), 'short');
      if (!$is_current) {
        $link = $revision->toLink($date, 'revision');
      }
      else {
        $link = $revision->toLink($date);
      }
    }
    else {
      $link = $revision->toLink($revision->label(), 'revision');
    }

    $markup = '';
    if ($revision instanceof EntityRevisionLogInterface) {
      $markup = $revision->getRevisionLogMessage();
    }

    if ($username) {
      $template = '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}';
    }
    else {
      $template = '{% trans %} {{ date }} {% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}';
    }

    $column = [
      'data' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => [
          'date' => $link,
          'username' => $username,
          'message' => ['#markup' => $markup, '#allowed_tags' => Xss::getHtmlTagList()],
        ],
      ],
    ];
    return $column;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasRevertRevisionAccess(EntityInterface $entity) {
    return AccessResult::allowedIfHasPermission($this->currentUser(), "revert all {$entity->getEntityTypeId()} revisions")->orIf(
      AccessResult::allowedIfHasPermission($this->currentUser(), "revert {$entity->bundle()} {$entity->getEntityTypeId()} revisions")
    );
  }

}
