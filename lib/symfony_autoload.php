<?php

if (!sfConfig::get('sf_in_bootstrap'))
{
  $sf_symfony_lib_dir = sfConfig::get('sf_symfony_lib_dir');

  // YAML support
  require_once($sf_symfony_lib_dir.'/util/Spyc.class.php');
  require_once($sf_symfony_lib_dir.'/util/sfYaml.class.php');

  // cache support
  require_once($sf_symfony_lib_dir.'/cache/sfCache.class.php');
  require_once($sf_symfony_lib_dir.'/cache/sfFileCache.class.php');

  // config support
  require_once($sf_symfony_lib_dir.'/config/sfConfigCache.class.php');
  require_once($sf_symfony_lib_dir.'/config/sfConfigHandler.class.php');
  require_once($sf_symfony_lib_dir.'/config/sfYamlConfigHandler.class.php');
  require_once($sf_symfony_lib_dir.'/config/sfAutoloadConfigHandler.class.php');
  require_once($sf_symfony_lib_dir.'/config/sfRootConfigHandler.class.php');

  // basic exception classes
  require_once($sf_symfony_lib_dir.'/exception/sfException.class.php');
  require_once($sf_symfony_lib_dir.'/exception/sfAutoloadException.class.php');
  require_once($sf_symfony_lib_dir.'/exception/sfCacheException.class.php');
  require_once($sf_symfony_lib_dir.'/exception/sfConfigurationException.class.php');
  require_once($sf_symfony_lib_dir.'/exception/sfParseException.class.php');

  // utils
  require_once($sf_symfony_lib_dir.'/util/sfParameterHolder.class.php');
}

/**
 * Handles autoloading of classes that have been specified in autoload.yml.
 *
 * @param string A class name.
 *
 * @return void
 */
function __autoload($class)
{
  if (!sfConfig::get('sf_class_autoload_loaded'))
  {
    try
    {
      // load the list of autoload classes
      $config = sfConfigCache::checkConfig(sfConfig::get('sf_app_config_dir_name').'/autoload.yml');

      sfConfig::set('sf_class_autoload_loaded', true);
    }
    catch (sfException $e)
    {
      $e->printStackTrace();
    }
    catch (Exception $e)
    {
      // unknown exception
      $e = new sfException($e->getMessage());

      $e->printStackTrace();
    }

    require_once($config);
  }

  $classes = sfConfig::get('sf_class_autoload', array());
  if (!isset($classes[$class]))
  {
    if (sfContext::hasInstance())
    {
      // see if the file exists in the current module lib directory
      // must be in a module context
      $current_module = sfContext::getInstance()->getModuleName();
      if ($current_module)
      {
        $module_lib = sfConfig::get('sf_app_module_dir').'/'.$current_module.'/'.sfConfig::get('sf_app_module_lib_dir_name').'/'.$class.'.class.php';
        if (is_readable($module_lib))
        {
          require_once($module_lib);

          return;
        }
      }
    }

    // unspecified class
    $error = 'Autoloading of class "%s" failed. Try to clear the symfony cache and refresh. [err0003]';
    $error = sprintf($error, $class);
    $e = new sfAutoloadException($error);

    $e->printStackTrace();
  }
  else
  {
    // class exists, let's include it
    require_once($classes[$class]);
  }
}

?>