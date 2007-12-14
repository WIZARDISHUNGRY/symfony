<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(19, new lime_output_color());

$v1 = new sfValidatorString();
$v2 = new sfValidatorString();

$e1 = new sfValidatorError($v1, 'max_length', array('value' => 'foo', 'max_length' => 1));
$e2 = new sfValidatorError($v2, 'min_length', array('value' => 'bar', 'min_length' => 5));

$e = new sfValidatorErrorSchema($v1);

// __construct()
$t->diag('__construct()');
$t->is($e->getValidator(), $v1, '__construct() takes a sfValidator as its first argument');
$e = new sfValidatorErrorSchema($v1, array('e1' => $e1, 'e2' => $e2));
$t->is($e->getErrors(), array('e1' => $e1, 'e2' => $e2), '__construct() can take an array of sfValidatorError as its second argument');

// ->addError() ->getErrors()
$t->diag('->addError() ->getErrors()');
$e = new sfValidatorErrorSchema($v1);
$e->addError($e1);
$e->addError($e2, 'e2');
$e->addError($e1, 2);
$t->is($e->getErrors(), array($e1, 'e2' => $e2, 2 => $e1), '->addError() adds an error to the error schema');

// ->getGlobalErrors()
$t->diag('->getGlobalErrors()');
$t->is($e->getGlobalErrors(), array($e1), '->getGlobalErrors() returns all globals/non named errors');

// ->getNamedErrors()
$t->diag('->getNamedErrors()');
$t->is($e->getNamedErrors(), array('e2' => $e2, 2 => $e1), '->getNamedErrors() returns all named errors');

// ->getValue()
$t->diag('->getValue()');
$t->is($e->getValue(), null, '->getValue() always returns null');

// ->getArguments()
$t->diag('->getArguments()');
$t->is($e->getArguments(), array(), '->getArguments() always returns an empty array');
$t->is($e->getArguments(true), array(), '->getArguments() always returns an empty array');

// ->getMessageFormat()
$t->diag('->getMessageFormat()');
$t->is($e->getMessageFormat(), '', '->getMessageFormat() always returns an empty string');

// ->getMessage()
$t->diag('->getMessage()');
$t->is($e->getMessage(), '"foo" is too long (1 characters max). e2 ["bar" is too short (5 characters min).] 2 ["foo" is too long (1 characters max).]', '->getMessage() returns the error message string');

// ->getCode()
$t->diag('->getCode()');
$t->is($e->getCode(), 'max_length e2 [min_length] 2 [max_length]', '->getCode() returns the error code');

// implements Countable
$t->diag('implements Countable');
$e = new sfValidatorErrorSchema($v1, array('e1' => $e1, 'e2' => $e2));
$t->is(count($e), 2, '"sfValidatorError" implements Countable');

// implements Iterator
$t->diag('implements Iterator');
$e = new sfValidatorErrorSchema($v1, array('e1' => $e1, 2 => $e2, $e2));
$errors = array();
foreach ($e as $name => $error)
{
  $errors[$name] = $error;
}
$t->is($errors, array('e1' => $e1, 2 => $e2, $e2), 'sfValidatorErrorSchema implements the Iterator interface');

// implements ArrayAccess
$t->diag('implements ArrayAccess');
$e = new sfValidatorErrorSchema($v1, array('e1' => $e1, $e2, 2 => $e2));
$t->is($e['e1'], $e1, 'sfValidatorErrorSchema implements the ArrayAccess interface');
$t->is($e[0], $e2, 'sfValidatorErrorSchema implements the ArrayAccess interface');
$t->is($e[2], $e2, 'sfValidatorErrorSchema implements the ArrayAccess interface');
$t->is(isset($e['e1']), true, 'sfValidatorErrorSchema implements the ArrayAccess interface');
$t->is(isset($e['e2']), false, 'sfValidatorErrorSchema implements the ArrayAccess interface');
try
{
  $e['e1'] = $e2;
  $t->fail('sfValidatorErrorSchema implements the ArrayAccess interface');
}
catch (LogicException $e)
{
  $t->pass('sfValidatorErrorSchema implements the ArrayAccess interface');
}