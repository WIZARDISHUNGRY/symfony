<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfViewParameterHolder stores all variables that will be available to the template.
 *
 * It can also escape variables with an escaping method.
 *
 * @package    symfony
 * @subpackage view
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfViewParameterHolder extends sfParameterHolder
{
  protected
    $dispatcher     = null,
    $escaping       = null,
    $escapingMethod = null;

  /**
   * Constructor.
   */
  public function __construct(sfEventDispatcher $dispatcher, $parameters = array(), $options = array())
  {
    $this->initialize($dispatcher, $parameters, $options);
  }

  /**
   * Initializes this view parameter holder.
   *
   * @param  sfEventDispatcher A sfEventDispatcher instance.
   * @param  array             An associative array of initialization parameters.
   * @param  array             An associative array of options.
   *
   * <b>Options:</b>
   *
   * # <b>escaping_strategy</b> - [off]              - The escaping strategy (on or off)
   * # <b>escaping_method</b>   - [ESC_SPECIALCHARS] - The escaping method (ESC_RAW, ESC_ENTITIES, ESC_JS, ESC_JS_NO_ENTITIES, or ESC_SPECIALCHARS)
   *
   * @return Boolean   true, if initialization completes successfully, otherwise false.
   *
   * @throws <b>sfInitializationException</b> If an error occurs while initializing this view parameter holder.
   */
  public function initialize(sfEventDispatcher $dispatcher, $parameters = array(), $options = array())
  {
    $this->dispatcher = $dispatcher;

    $event = $dispatcher->filter(new sfEvent($this, 'template.filter_parameters'), $parameters);
    $parameters = $event->getReturnValue();

    $this->add($parameters);

    $this->setEscaping(isset($options['escaping_strategy']) ? $options['escaping_strategy'] : false);
    $this->setEscapingMethod(isset($options['escaping_method']) ? $options['escaping_method'] : 'ESC_SPECIALCHARS');
  }

  /**
   * Returns true if the current object acts as an escaper.
   *
   * @return Boolean true if the current object acts as an escaper, false otherwise
   */
  public function isEscaped()
  {
    return in_array($this->getEscaping(), array('on', true), true);
  }

  /**
   * Returns an array representation of the view parameters.
   *
   * @return array An array of view parameters
   *
   * @throws InvalidArgumentException
   */
  public function toArray()
  {
    $attributes = array();

    if ($this->isEscaped())
    {
      $attributes['sf_data'] = sfOutputEscaper::escape($this->getEscapingMethod(), $this->getAll());
      foreach ($attributes['sf_data'] as $key => $value)
      {
        $attributes[$key] = $value;
      }
    }
    else if (in_array($this->getEscaping(), array('off', false), true))
    {
      $attributes = $this->getAll();
      $attributes['sf_data'] = sfOutputEscaper::escape(ESC_RAW, $this->getAll());
    }
    else
    {
      throw new InvalidArgumentException(sprintf('Unknown strategy "%s".', $this->getEscaping()));
    }

    return $attributes;
  }

  /**
   * Gets the default escaping strategy associated with this view.
   *
   * The escaping strategy specifies how the variables get passed to the view.
   *
   * @return string the escaping strategy
   */
  public function getEscaping()
  {
    return $this->escaping;
  }

  /**
   * Sets the escape character strategy.
   *
   * @param string Escape code
   */
  public function setEscaping($escaping)
  {
    $this->escaping = $escaping;
  }

  /**
   * Returns the name of the function that is to be used as the escaping method.
   *
   * If the escaping method is empty, then that is returned. The default value
   * specified by the sub-class will be used. If the method does not exist (in
   * the sense there is no define associated with the method), an exception is
   * thrown.
   *
   * @return string The escaping method as the name of the function to use
   *
   * @throws InvalidArgumentException If the method does not exist
   */
  public function getEscapingMethod()
  {
    if (empty($this->escapingMethod))
    {
      return $this->escapingMethod;
    }

    if (!defined($this->escapingMethod))
    {
      throw new InvalidArgumentException(sprintf('The escaping method "%s" is not available.', $this->escapingMethod));
    }

    return constant($this->escapingMethod);
  }

  /**
   * Sets the escaping method for the current view.
   *
   * @param string Method for escaping
   */
  public function setEscapingMethod($method)
  {
    $this->escapingMethod = $method;
  }

  /**
   * Serializes the current instance.
   *
   * @return array Objects instance
   */
  public function serialize()
  {
    $this->set('_sf_escaping_method', $this->escapingMethod);
    $this->set('_sf_escaping', $this->escaping);

    $tmp = clone $this;
    foreach ($tmp->getNames() as $key)
    {
      if (0 === strpos($key, 'sf_'))
      {
        $tmp->remove($key);
      }
    }
    $tmp->dispatcher = null;

    $serialized = serialize($tmp->getAll());

    $this->remove('_sf_escaping_method');
    $this->remove('_sf_escaping');

    return $serialized;
  }

  /**
   * Unserializes a sfViewParameterHolder instance.
   */
  public function unserialize($serialized)
  {
    parent::unserialize($serialized);

    $this->initialize(sfContext::hasInstance() ? sfContext::getInstance()->getEventDispatcher() : new sfEventDispatcher());

    $this->setEscapingMethod($this->remove('_sf_escaping_method'));
    $this->setEscaping($this->remove('_sf_escaping'));
  }
}