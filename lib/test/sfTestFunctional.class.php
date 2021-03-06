<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfTestFunctional tests an application by using a browser simulator.
 *
 * @package    symfony
 * @subpackage test
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfTestFunctional extends sfTestFunctionalBase
{
  /**
   * Initializes the browser tester instance.
   *
   * @param sfBrowserBase $browser A sfBrowserBase instance
   * @param lime_test     $lime    A lime instance
   */
  public function __construct(sfBrowserBase $browser, lime_test $lime = null, $testers = array())
  {
    $testers['view_cache'] = 'sfTesterViewCache';
    $testers['form'] = 'sfTesterForm';

    parent::__construct($browser, $lime, $testers);
  }

  /**
   * Checks that the request is forwarded to a given module/action.
   *
   * @param  string $moduleName  The module name
   * @param  string $actionName  The action name
   * @param  mixed  $position    The position in the action stack (default to the last entry)
   *
   * @return sfTestBrowser The current sfTestBrowser instance
   */
  public function isForwardedTo($moduleName, $actionName, $position = 'last')
  {
    $actionStack = $this->browser->getContext()->getActionStack();

    switch ($position)
    {
      case 'first':
        $entry = $actionStack->getFirstEntry();
        break;
      case 'last':
        $entry = $actionStack->getLastEntry();
        break;
      default:
        $entry = $actionStack->getEntry($position);
    }

    $this->test()->is($entry->getModuleName(), $moduleName, sprintf('request is forwarded to the "%s" module (%s)', $moduleName, $position));
    $this->test()->is($entry->getActionName(), $actionName, sprintf('request is forwarded to the "%s" action (%s)', $actionName, $position));

    return $this;
  }

  /**
   * Tests if the given uri is cached.
   *
   * @deprecated since 1.2
   *
   * @param  boolean $boolean      Flag for checking the cache
   * @param  boolean $with_layout  If have or not layout
   *
   * @return sfTestBrowser The current sfTestBrowser instance
   */
  public function isCached($boolean, $with_layout = false)
  {
    return $this->with('view_cache')->isCached($boolean, $with_layout);
  }

  /**
   * Tests if the given uri is cached.
   *
   * @deprecated since 1.2
   *
   * @param  string  $uri          Uniform resource identifier
   * @param  boolean $boolean      Flag for checking the cache
   * @param  boolean $with_layout  If have or not layout
   *
   * @return sfTestBrowser The current sfTestBrowser instance
   */
  public function isUriCached($uri, $boolean, $with_layout = false)
  {
    return $this->with('view_cache')->isUriCached($uri, $boolean, $with_layout);
  }
}
