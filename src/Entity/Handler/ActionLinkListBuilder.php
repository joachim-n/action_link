<?php

namespace Drupal\action_link\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides the list builder handler for the Action Link entity.
 */
class ActionLinkListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['name'] = $this->t('Name');
    $header['state_action'] = $this->t('Action');
    $header['link_style'] = $this->t('Link style');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['name'] = $entity->label();
    $row['state_action'] = $entity->getStateActionPlugin()->getPluginDefinition()['label'];
    $row['link_style'] = $entity->getLinkStylePlugin()?->getPluginDefinition()['label'] ?? '';
    return $row + parent::buildRow($entity);
  }

}
