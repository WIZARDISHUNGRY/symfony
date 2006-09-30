<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$_test_dir = realpath(dirname(__FILE__).'/../..');
require_once($_test_dir.'/../lib/vendor/lime/lime.php');
require_once($_test_dir.'/unit/sfContextMock.class.php');
require_once($_test_dir.'/unit/bootstrap.php');

$t = new lime_test(12, new lime_output_color());

$context = new sfContext();
$v = new sfUrlValidator();
$v->initialize($context);

// ->execute()
$t->diag('->execute()');

$validUrls = array(
  'http://www.google.com',
  'https://google.com/',
  'http://www.symfony-project.com/',
  'ftp://www.symfony-project.com/file.tgz',
);

$invalidUrls = array(
  'google.com',
  'http:/google.com',
);

$v->initialize($context);
foreach ($validUrls as $value)
{
  $error = null;
  $t->ok($v->execute($value, $error), sprintf('->execute() returns true for a valid URL "%s"', $value));
  $t->is($error, null, '->execute() doesn\'t change "$error" if it returns true');
}

foreach ($invalidUrls as $value)
{
  $error = null;
  $t->ok(!$v->execute($value, $error), sprintf('->execute() returns false for an invalid URL "%s"', $value));
  $t->isnt($error, null, '->execute() changes "$error" with a default message if it returns false');
}
