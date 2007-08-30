<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Generates a new module.
 *
 * @package    symfony
 * @subpackage command
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfGenerateModuleTask extends sfGeneratorBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
      new sfCommandArgument('module', sfCommandArgument::REQUIRED, 'The module name'),
    ));

    $this->aliases = array('init-module');
    $this->namespace = 'generate';
    $this->name = 'module';

    $this->briefDescription = 'Generates a new module';

    $this->detailedDescription = <<<EOF
The [generate:module|INFO] task creates the basic directory structure
for a new module in an existing application:

  [./symfony generate:module frontend article|INFO]

The task can also change the author name found in the [actions.class.php|COMMENT]
if you have configure it in [config/properties.ini|COMMENT]:

  [symfony]
    name=blog
    author=Fabien Potencier <fabien.potencier@sensio.com>

You can customize the default skeleton used by the task by creating a
[%sf_data_dir%/skeleton/module|COMMENT] directory.

The task also creates a functional test stub named
[%sf_test_dir%/functional/%application%/%module%ActionsTest.class.php|COMMENT]
that does not pass by default.

If a module with the same name already exists in the application,
it throws a [sfCommandException|COMMENT].
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $app       = $arguments['application'];
    $module    = $arguments['module'];

    $moduleDir = sfConfig::get('sf_root_dir').'/'.sfConfig::get('sf_apps_dir_name').'/'.$app.'/'.sfConfig::get('sf_app_module_dir_name').'/'.$module;

    if (is_dir($moduleDir))
    {
      throw new sfCommandException(sprintf('The module "%s" already exists in the "%s" application.', $moduleDir, $app));
    }

    $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);

    $constants = array(
      'PROJECT_NAME' => isset($properties['name']) ? $properties['name'] : 'symfony',
      'APP_NAME'     => $app,
      'MODULE_NAME'  => $module,
      'AUTHOR_NAME'  => isset($properties['author']) ? $properties['author'] : 'Your name here',
    );

    if (is_readable(sfConfig::get('sf_data_dir').'/skeleton/module'))
    {
      $skeletonDir = sfConfig::get('sf_data_dir').'/skeleton/module';
    }
    else
    {
      $skeletonDir = sfConfig::get('sf_symfony_data_dir').'/skeleton/module';
    }

    // create basic application structure
    $finder = sfFinder::type('any')->ignore_version_control()->discard('.sf');
    $this->filesystem->mirror($skeletonDir.'/module', $moduleDir, $finder);

    // create basic test
    $this->filesystem->copy($skeletonDir.'/test/actionsTest.php', sfConfig::get('sf_root_dir').'/test/functional/'.$app.'/'.$module.'ActionsTest.php');

    // customize test file
    $this->filesystem->replaceTokens(sfConfig::get('sf_root_dir').'/test/functional/'.$app.DIRECTORY_SEPARATOR.$module.'ActionsTest.php', '##', '##', $constants);

    // customize php and yml files
    $finder = sfFinder::type('file')->name('*.php', '*.yml');
    $this->filesystem->replaceTokens($finder->in($moduleDir), '##', '##', $constants);
  }
}