<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Generates Propel model, SQL and initializes the database.
 *
 * @package    symfony
 * @subpackage command
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelBuildAllTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->aliases = array('propel-build-all');
    $this->namespace = 'propel';
    $this->name = 'build-all';
    $this->briefDescription = 'Generates Propel model, SQL and initializes the database';

    $this->detailedDescription = <<<EOF
The [propel:build-all|INFO] task is a shortcut for three other tasks:

  [./symfony propel:build-all|INFO]

The task is equivalent to:

  [./symfony propel:build-model|INFO]
  [./symfony propel:build-sql|INFO]
  [./symfony propel:insert-sql|INFO]

See those three tasks help page for more information.
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $commandApplication = is_null($this->commandApplication) ? null : $this->commandApplication;

    $buildModel = new sfPropelBuildModelTask($commandApplication);
    $buildModel->run();

    $buildSql = new sfPropelBuildSqlTask($commandApplication);
    $buildSql->run();

    $insertSql = new sfPropelInsertSqlTask($commandApplication);
    $insertSql->run();
  }
}