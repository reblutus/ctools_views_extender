<?php
/**
 * @file
 * Contains \Drupal\ctools_views_extender\Plugin\Display\Block.
 */

namespace Drupal\ctools_views_extender\Plugin\Display;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\ctools_views\Plugin\Display\Block as CtoolsBlock;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Provides a Block display plugin that allows for greater control over Views
 * block settings.
 */
class Block extends CtoolsBlock {

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    $filtered_allow = array_filter($this->getOption('allow'));
    $filter_options = [
      'background' => $this->t('Background')
    ];
    $filter_intersect = array_intersect_key($filter_options, $filtered_allow);

    $options['allow'] = array(
      'category' => 'block',
      'title' => $this->t('Allow settings'),
      'value' => empty($filtered_allow) ? $this->t('None') : implode(', ', $filter_intersect),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = $form['allow']['#options'];
    $options['background'] = $this->t('Background');
    $form['allow']['#options'] = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $allow_settings = array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();

    if(!empty($allow_settings['background'])){
      $form['override']['background'] = array(
        '#type' => 'managed_file',
        '#title' => $this->t('Background'),
        '#description' => $this->t('Enter background image'),
        '#default_value' => isset($block_configuration['background'])?$block_configuration['background']:'',
        '#upload_location' => 'public://backgrounds'
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    // Set default value for items_per_page if left blank.
    if (empty($form_state->getValue(array('override', 'items_per_page')))) {
      $form_state->setValue(array('override', 'items_per_page'), "none");
    }

    parent::blockSubmit($block, $form, $form_state);
    $configuration = $block->getConfiguration();
    $allow_settings = array_filter($this->getOption('allow'));

    if (!empty($allow_settings['background'])) {
        $background = $form_state->getValue(['override', 'background']);
        $configuration['background'] = $background;
        // fix bug in Drupal which does not put the image as permanent
//      kint($form_state->getValue(['override', 'background'])[0]);
        $fid = $form_state->getValue(['override', 'background'])[0];
        /** @var \Drupal\file\Entity\File $file */
        $file = \Drupal\file\Entity\File::load($fid);
        /** @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage */
        $file_usage = \Drupal::service('file.usage');
        $file_usage->add($file, 'ctools_views', 'image', 1);
    }

    $block->setConfiguration($configuration);
  }
}
