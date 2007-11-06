<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfValidatorSchemaFilter executes non schema validator on a schema input value.
 *
 * @package    symfony
 * @subpackage validator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfValidatorSchemaFilter extends sfValidatorSchema
{
  /**
   * Constructor.
   *
   * @param string      The field name
   * @param sfValidator The validator
   * @param array       An array of options
   * @param array       An array of error messages
   *
   * @see sfValidator
   */
  public function __construct($field, sfValidator $validator, $options = array(), $messages = array())
  {
    $this->addOption('field', $field);
    $this->addOption('validator', $validator);

    parent::__construct(null, $options, $messages);
  }

  /**
   * @see sfValidator
   */
  protected function doClean($values)
  {
    if (is_null($values))
    {
      $values = array();
    }

    if (!is_array($values))
    {
      throw new sfException('You must pass an array parameter to the clean() method');
    }

    $value = isset($values[$this->getOption('field')]) ? $values[$this->getOption('field')] : null;

    $values[$this->getOption('field')] = $this->getOption('validator')->clean($value);

    return $values;
  }

  /**
   * @see sfValidator
   */
  public function asString($indent = 0)
  {
    return sprintf('%s%s:%s', str_repeat(' ', $indent), $this->getOption('field'), $this->getOption('validator')->asString(0));
  }
}
