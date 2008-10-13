<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfRoutingConfigHandler extends sfYamlConfigHandler
{
  /**
   * Executes this configuration handler.
   *
   * @param array $configFiles An array of absolute filesystem path to a configuration file
   *
   * @return string Data to be written to a cache file
   *
   * @throws sfConfigurationException If a requested configuration file does not exist or is not readable
   * @throws sfParseException         If a requested configuration file is improperly formatted
   */
  public function execute($configFiles)
  {
    $routes = $this->parse($configFiles);

    $data = array();
    foreach ($routes as $name => $route)
    {
      $arguments = array();
      foreach ($route[1] as $argument)
      {
        $arguments[] = is_array($argument) ? var_export($argument, true) : sprintf("'%s'", $argument);
      }

      $data[] = sprintf('\'%s\' => new %s(%s),', $name, $route[0], implode(', ', $arguments));
    }

    return sprintf("<?php\n".
                   "// auto-generated by sfRoutingConfigHandler\n".
                   "// date: %s\nreturn array(\n%s\n);\n", date('Y/m/d H:i:s'), implode("\n", $data)
    );
  }

  public function evaluate($configFiles)
  {
    $routeDefinitions = $this->parse($configFiles);

    $routes = array();
    foreach ($routeDefinitions as $name => $route)
    {
      $arguments = array();
      foreach ($route[1] as $argument)
      {
        $arguments[] = is_array($argument) ? var_export($argument, true) : sprintf("'%s'", $argument);
      }

      $r = new ReflectionClass($route[0]);
      $routes[$name] = $r->newInstanceArgs($route[1]);
    }

    return $routes;
  }

  protected function parse($configFiles)
  {
    // parse the yaml
    $config = self::getConfiguration($configFiles);

    // collect routes
    $routes = array();
    foreach ($config as $name => $params)
    {
      if (
        (isset($params['type']) && 'collection' == $params['type'])
        ||
        (isset($params['class']) && false !== strpos($params['class'], 'Collection'))
      )
      {
        $options = isset($params['options']) ? $params['options'] : array();
        $options['name'] = $name;
        $options['requirements'] = isset($params['requirements']) ? $params['requirements'] : array();

        $routes[$name] = array(isset($params['class']) ? $params['class'] : 'sfRouteCollection', array($options));
      }
      else
      {
        $routes[$name] = array(isset($params['class']) ? $params['class'] : 'sfRoute', array(
          $params['url'] ? $params['url'] : '/',
          isset($params['params']) ? $params['params'] : (isset($params['param']) ? $params['param'] : array()),
          isset($params['requirements']) ? $params['requirements'] : array(),
          isset($params['options']) ? $params['options'] : array(),
        ));
      }
    }

    return $routes;
  }

  /**
   * @see sfConfigHandler
   */
  static public function getConfiguration(array $configFiles)
  {
    return self::parseYamls($configFiles);
  }
}
