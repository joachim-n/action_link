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
    $header['plugin'] = $this->t('Action');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['name'] = $entity->label();
    $row['plugin'] = $entity->getStateActionPlugin()->getPluginDefinition()['label'];
    return $row + parent::buildRow($entity);
  }

}
