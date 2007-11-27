<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfForm represents a form.
 *
 * A forms is composed of a validator schema and a widget form schema.
 *
 * sfForm also takes care of CSRF protection by default.
 *
 * @package    symfony
 * @subpackage form
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfForm implements ArrayAccess
{
  protected static
    $CSRFProtection = true,
    $CSRFSecret     = null,
    $CSRFFieldName  = '_csrf_token';

  protected
    $validatorSchema = null,
    $widgetSchema    = null,
    $errorSchema     = null,
    $formField       = null,
    $formFields      = array(),
    $isBound         = false,
    $taintedValues   = array(),
    $values          = null,
    $defaults        = array();

  /**
   * Constructor.
   *
   * @param array  An array of field default values
   * @param string A CSRF secret (false to disable CSRF protection, null to use the global CSRF secret)
   */
  public function __construct($defaults = array(), $CSRFSecret = null)
  {
    $this->setDefaults($defaults);

    $this->validatorSchema = new sfValidatorSchema();
    $this->widgetSchema    = new sfWidgetFormSchema();
    $this->errorSchema     = new sfValidatorErrorSchema($this->validatorSchema);

    $this->setup();
    $this->configure();

    $this->addCSRFProtection($CSRFSecret);
    $this->resetFormFields();
  }

  /**
   * Returns a string representation of the form.
   *
   * @return string A string representation of the form
   *
   * @see render()
   */
  public function __toString()
  {
    return $this->render();
  }

  /**
   * Configures the current form.
   */
  public function configure()
  {
  }

  /**
   * Setups the current form.
   *
   * This method is overridden by generator.
   *
   * If you want to do something at initialization, you have to override the configure() method.
   *
   * @see configure()
   */
  public function setup()
  {
  }

  /**
   * Renders the widget schema associated with this form.
   *
   * @return string The rendered widget schema
   */
  public function render()
  {
    return $this->getFormField()->render();
  }

  /**
   * Binds the form with input values.
   *
   * It triggers the validator schema validation.
   *
   * @param array An array of input values
   */
  public function bind($taintedValues)
  {
    $this->taintedValues = $taintedValues;
    $this->isBound = true;
    $this->resetFormFields();

    try
    {
      $this->values = $this->validatorSchema->clean($this->taintedValues);
      $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

      // remove CSRF token
      unset($this->values[self::$CSRFFieldName]);
    }
    catch (sfValidatorErrorSchema $e)
    {
      $this->values = array();
      $this->errorSchema = $e;
    }
  }

  /**
   * Returns true if the form is bound to input values.
   *
   * @return Boolean true if the form is bound to input values, false otherwise
   */
  public function isBound()
  {
    return $this->isBound;
  }

  /**
   * Returns true if the form is valid.
   *
   * It returns false if the form is not bound.
   *
   * @return Boolean true if the form is valid, false otherwise
   */
  public function isValid()
  {
    if (!$this->isBound)
    {
      return false;
    }

    return 0 == count($this->errorSchema);
  }

  /**
   * Returns the array of cleaned values.
   *
   * If the form is not bound, it returns an empty array.
   *
   * @return array An array of cleaned values
   */
  public function getValues()
  {
    return $this->isBound ? $this->values : array();
  }

  /**
   * Gets the error schema associated with the form.
   *
   * @return sfValidatorErrorSchema A sfValidatorErrorSchema instance
   */
  public function getErrorSchema()
  {
    return $this->errorSchema;
  }

  /**
   * Embeds a sfForm into the current form.
   *
   * @param string The field name
   * @param sfForm A sfForm instance
   * @param string The format to use for widget name
   * @param string A HTML decorator for the embedded form
   */
  public function embedForm($name, sfForm $form, $nameFormat = null, $decorator = null)
  {
    // change the name format for the embedded widget
    if (is_null($nameFormat))
    {
      $nameFormat = $this->generateNameFormatForEmbedded($name, $this->widgetSchema->getNameFormat());
    }

    $form = clone $form;
    unset($form[self::$CSRFFieldName]);

    $widgetSchema = $form->getWidgetSchema();
    $widgetSchema->setNameFormat($nameFormat);

    $this->setDefault($name, $form->getDefaults());

    $decorator = is_null($decorator) ? $widgetSchema->getFormFormatter()->getDecoratorFormat() : $decorator;

    $this->widgetSchema[$name] = new sfWidgetFormSchemaDecorator($widgetSchema, $decorator);
    $this->validatorSchema[$name] = $form->getValidatorSchema();

    $this->resetFormFields();
  }

  /**
   * Embeds a sfForm into the current form n times.
   *
   * @param string  The field name
   * @param sfForm  A sfForm instance
   * @param integer The number of times to include the form
   * @param string  The format to use for widget name
   * @param string  A HTML decorator for the main form around embedded forms
   * @param string  A HTML decorator for each embedded form
   */
  public function embedFormForEach($name, sfForm $form, $n, $nameFormat = null, $decorator = null, $innerDecorator = null, $attributes = array(), $options = array(), $labels = array())
  {
    // change the name format for the embedded widget
    if (is_null($nameFormat))
    {
      $nameFormat = $this->generateNameFormatForEmbedded($name, $this->widgetSchema->getNameFormat());
    }

    $form = clone $form;
    unset($form[self::$CSRFFieldName]);

    // generate labels and default values
    $defaults = array();
    for ($i = 0; $i < $n; $i++)
    {
      if (!isset($labels[$i]))
      {
        $labels[$i] = sprintf('%s (%s)', $form->getWidgetSchema()->generateLabelName($name), $i);
      }

      $defaults[$i] = $form->getDefaults();
    }

    $this->setDefault($name, $defaults);

    $decorator = is_null($decorator) ? $form->getWidgetSchema()->getFormFormatter()->getDecoratorFormat() : $decorator;
    $innerDecorator = is_null($innerDecorator) ? $form->getWidgetSchema()->getFormFormatter()->getDecoratorFormat() : $innerDecorator;

    $this->widgetSchema[$name] = new sfWidgetFormSchemaDecorator(new sfWidgetFormSchemaForEach($nameFormat, new sfWidgetFormSchemaDecorator($form->getWidgetSchema(), $innerDecorator), $n, $attributes, $options, $labels), $decorator);
    $this->validatorSchema[$name] = new sfValidatorSchemaForEach($form->getValidatorSchema(), $n);

    $this->resetFormFields();
  }

  /**
   * Sets the validator schema associated with this form.
   *
   * @param sfValidatorSchema A sfValidatorSchema instance
   */
  public function setValidatorSchema(sfValidatorSchema $validatorSchema)
  {
    $this->validatorSchema = $validatorSchema;

    $this->resetFormFields();
  }

  /**
   * Gets the validator schema associated with this form.
   *
   * @return sfValidatorSchema A sfValidatorSchema instance
   */
  public function getValidatorSchema()
  {
    return $this->validatorSchema;
  }

  /**
   * Sets the widget schema associated with this form.
   *
   * @param sfWidgetFormSchema A sfWidgetFormSchema instance
   */
  public function setWidgetSchema(sfWidgetFormSchema $widgetSchema)
  {
    $this->widgetSchema = $widgetSchema;

    $this->resetFormFields();
  }

  /**
   * Gets the widget schema associated with this form.
   *
   * @return sfWidgetFormSchema A sfWidgetFormSchema instance
   */
  public function getWidgetSchema()
  {
    return $this->widgetSchema;
  }

  /**
   * Sets a default value for a form field.
   *
   * @param string The field name
   * @param mixed  The default value
   */
  public function setDefault($name, $default)
  {
    $this->defaults[$name] = $default;

    $this->resetFormFields();
  }

  /**
   * Gets a default value for a form field.
   *
   * @param string The field name
   *
   * @param mixed  The default value
   */
  public function getDefault($name)
  {
    return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
  }

  /**
   * Returns true if the form has a default value for a form field.
   *
   * @param string  The field name
   *
   * @param Boolean true if the form has a default value for this field, false otherwise
   */
  public function hasDefault($name)
  {
    return array_key_exists($name, $this->defaults);
  }

  /**
   * Sets the default values for the form.
   *
   * The default values are only used if the form is not bound.
   *
   * @param array An array of default values
   */
  public function setDefaults($defaults)
  {
    $this->defaults = $defaults;

    $this->resetFormFields();
  }

  /**
   * Gets the default values for the form.
   *
   * @return array An array of default values
   */
  public function getDefaults()
  {
    return $this->defaults;
  }

  /**
   * Adds CSRF protection to the current form.
   *
   * @param string The secret to use to compute the CSRF token
   */
  public function addCSRFProtection($secret)
  {
    if (false === $secret || (is_null($secret) && !self::$CSRFProtection))
    {
      return;
    }

    if (is_null($secret))
    {
      if (is_null(self::$CSRFSecret))
      {
        self::$CSRFSecret = md5(__FILE__.php_uname());
      }

      $secret = self::$CSRFSecret;
    }

    $token = $this->getCSRFToken($secret);

    $this->validatorSchema[self::$CSRFFieldName] = new sfValidatorCSRFToken(array('token' => $token));
    $this->widgetSchema[self::$CSRFFieldName] = new sfWidgetFormInputHidden();
    $this->setDefault(self::$CSRFFieldName, $token);
  }

  /**
   * Returns a CSRF token, given a secret.
   *
   * If you want to change the algorithm used to compute the token, you
   * can override this method.
   *
   * @param  string The secret string to use
   *
   * @return string A token string
   */
  public function getCSRFToken($secret)
  {
    return md5($secret.session_id().get_class($this));
  }

  /**
   * Returns true if this form is CSRF protected
   */
  public function isCSRFProtected()
  {
    return !is_null($this->validatorSchema[self::$CSRFFieldName]);
  }

  /**
   * Sets the CSRF field name.
   *
   * @param string The CSRF field name
   */
  static public function setCSRFFieldName($name)
  {
    self::$CSRFFieldName = $name;
  }

  /**
   * Gets the CSRF field name.
   *
   * @return string The CSRF field name
   */
  static public function getCSRFFieldName()
  {
    return self::$CSRFFieldName;
  }

  /**
   * Enables CSRF protection for all forms.
   *
   * The given secret will be used for all forms, except if you pass a secret in the constructor.
   * Even if a secret is automatically generated if you don't provide a secret, you're strongly advised
   * to provide one by yourself.
   *
   * @param string A secret to use when computing the CSRF token
   */
  static public function enableCSRFProtection($secret = null)
  {
    self::$CSRFProtection = true;

    if (!is_null($secret))
    {
      self::$CSRFSecret = $secret;
    }
  }

  /**
   * Disables CSRF protection for all forms.
   */
  static public function disableCSRFProtection()
  {
    self::$CSRFProtection = false;
  }

  /**
   * Returns true if the form is multipart.
   *
   * @return Boolean true if the form is multipart
   */
  public function isMultipart()
  {
    return $this->widgetSchema->needsMultipartForm();
  }

  public function resetFormFields()
  {
    $this->formFields = array();
    $this->formField = null;
  }

  /**
   * Returns true if the bound field exists (implements the ArrayAccess interface).
   *
   * @param  string  The name of the bound field
   *
   * @return Boolean true if the widget exists, false otherwise
   */
  public function offsetExists($name)
  {
    return isset($this->widgetSchema[$name]);
  }

  /**
   * Returns the form field associated with the name (implements the ArrayAccess interface).
   *
   * @param  string        The offset of the value to get
   *
   * @return sfFormField   A form field instance
   */
  public function offsetGet($name)
  {
    if (!isset($this->formFields[$name]))
    {
      if (!$widget = $this->widgetSchema[$name])
      {
        throw new sfException(sprintf('Widget "%s" does not exist.', $name));
      }

      $values = $this->isBound ? $this->taintedValues : $this->defaults;

      $this->formFields[$name] = new sfFormField($widget, $this->getFormField(), $name, isset($values[$name]) ? $values[$name] : null, $this->errorSchema[$name]);
    }

    return $this->formFields[$name];
  }

  /**
   * Throws an exception saying that values cannot be set (implements the ArrayAccess interface).
   *
   * @param string (ignored)
   * @param string (ignored)
   *
   * @throws <b>sfException</b>
   */
  public function offsetSet($offset, $value)
  {
    throw new sfException('Cannot update form fields.');
  }

  /**
   * Removes a field from the form.
   *
   * It removes the widget and the validator for the given field.
   *
   * @param string The field name
   */
  public function offsetUnset($offset)
  {
    unset($this->widgetSchema[$offset], $this->validatorSchema[$offset]);

    $this->resetFormFields();
  }

  /**
   * Returns a form field for the main widget schema.
   *
   * @return sfFormField A sfFormField instance
   */
  protected function getFormField()
  {
    if (is_null($this->formField))
    {
      $this->formField = new sfFormField($this->widgetSchema, null, null, $this->isBound ? $this->taintedValues : $this->defaults, $this->errorSchema);
    }

    return $this->formField;
  }

  /**
   * Generates a name format for embedded forms.
   *
   * @param  string The widget name
   * @param  string The current name format
   *
   * @return string The name format to use for embedding
   *
   * @see embedFormForEach()
   * @see embedForm()
   */
  protected function generateNameFormatForEmbedded($name, $nameFormat)
  {
    // if current name format is something[%s], change it to something[$name][%s]
    // else change it to $name[%s]
    if ('[%s]' === substr($nameFormat, -4))
    {
      return sprintf('%s[%s][%%s]', substr($nameFormat, 0, -4), $name);
    }
    else
    {
      return sprintf('%s[%%s]', $name);
    }
  }
}