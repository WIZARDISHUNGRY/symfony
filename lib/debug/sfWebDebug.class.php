<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfWebDebug prints debug information in the browser for easy debugging.
 *
 * @package    symfony
 * @subpackage debug
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfWebDebug.class.php 467 2005-09-19 06:30:23Z fabien $
 */
class sfWebDebug
{
  private
    $log             = array(),
    $short_log       = array(),
    $max_priority    = 1000,
    $types           = array(),
    $last_time_log   = -1,
    $base_image_path = '/sf/images/sf_debug_stats';

  private static
    $instance        = null;

  protected
    $context         = null;

  public function initialize()
  {
  }

  /**
   * Retrieve the singleton instance of this class.
   *
   * @return sfWebDebug A sfWebDebug implementation instance.
   */
  public static function getInstance()
  {
    if (!isset(self::$instance))
    {
      $class = __CLASS__;
      self::$instance = new $class();
      self::$instance->initialize();
    }

    return self::$instance;
  }

  public function registerAssets()
  {
    if (!$this->context)
    {
      $this->context = sfContext::getInstance();
    }

    // load helpers
    $this->loadHelpers();

    // register our css and js
    $this->context->getRequest()->setAttribute(
      'sf_web_debug',
      array('/sf/js/prototype/prototype'),
      'helper/asset/auto/javascript'
    );

    $this->context->getRequest()->setAttribute(
      'sf_web_debug',
      array('/sf/css/sf_debug_stats/main'),
      'helper/asset/auto/stylesheet/last'
    );
  }

  public function logShortMessage($message)
  {
    $this->short_log[] = $message;
  }

  public function log($logEntry)
  {
    // elapsed time
    if ($this->last_time_log == -1)
    {
      $this->last_time_log = sfConfig::get('sf_timer_start');
    }

    $logEntry->setElapsedTime(sprintf('%.0f', (microtime(true) - $this->last_time_log) * 1000));
    $this->last_time_log = microtime(true);

    // update max priority
    if ($logEntry->getPriority() < $this->max_priority)
    {
      $this->max_priority = $logEntry->getPriority();
    }

    // update types
    if (!isset($this->types[$logEntry->getType()]))
    {
      $this->types[$logEntry->getType()] = 1;
    }
    else
    {
      ++$this->types[$logEntry->getType()];
    }

    $this->log[] = $logEntry;
  }

  public function printResults()
  {
    $this->loadHelpers();
    echo $this->getResults();
  }

  private function loadHelpers()
  {
    // require needed helpers
    foreach (array('Tag', 'Url', 'Asset', 'Javascript') as $helperName)
    {
      include_once(sfConfig::get('sf_symfony_lib_dir').'/helper/'.$helperName.'Helper.php');
    }
  }

  private function formatLogLine($type, $log_line)
  {
    // escape HTML
    $log_line = htmlspecialchars(strip_tags($log_line));

    foreach (array('sf_app_dir', 'sf_root_dir', 'sf_symfony_lib_dir', 'sf_symfony_data_dir') as $constant)
    {
      $log_line = str_replace(realpath(sfConfig::get($constant)), $constant, $log_line);
    }

    $log_line = preg_replace('/&quot;(.+?)&quot;/s', '&quot;<span class="sfStatsFileInfo">\\1</span>&quot;', $log_line);
    $log_line = preg_replace('/^(.+?)\(\)\:/s', '<span class="sfStatsFileInfo">\\1()</span>:', $log_line);

    // special formatting for creole/SQL lines
    if (strtolower($type) == 'creole')
    {
      $log_line = preg_replace('/\b(SELECT|FROM|AS|LIMIT|ASC|COUNT|DESC|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|ORDER BY|GROUP BY|IN|LIKE|DISTINCT)\b/', '<span class="sfStatsFileInfo">\\1</span>', $log_line);
    }

    return $log_line;
  }

  public function getResults()
  {
    if (!sfConfig::get('sf_web_debug'))
    {
      return '';
    }

    $result = '';

    // total time elapsed
    $total_time = 0;
    if (sfConfig::get('sf_debug'))
    {
      $total_time = (microtime(true) - sfConfig::get('sf_timer_start')) * 1000;
      $total_time = sprintf(($total_time < 1) ? '%.2f' : '%.0f', $total_time);
    }

    // max priority
    $log_image = '';
    if (sfConfig::get('sf_logging_active'))
    {
      if ($this->max_priority >= 6)
      {
        $log_image = 'info';
      }
      else if ($this->max_priority >= 4)
      {
        $log_image = 'warning';
      }
      else
      {
        $log_image = 'error';
      }
    }

    // short messages
    $short_messages = '';
    if ($this->short_log)
    {
      $short_messages = '<div id="sfStatsShortMessages">&raquo;&nbsp;'.implode('<br />&raquo;&nbsp;', $this->short_log).'</div>';
    }

    $result .= '
      <div class="sfStats" id="sfStats'.ucfirst($log_image).'">
      '.$this->displayMenu($log_image).'
      <div id="sfStatsDetails">'.$this->displayCurrentConfig().'</div>
      <div id="sfStatsTime">processed in <strong>'.$total_time.'</strong> ms</div>
      '.$short_messages.'
      </div>
    ';

    if (sfConfig::get('sf_logging_active'))
    {
      $logs  = '<table id="sfStatsLogs">';
      $logs .= "<tr>
        <th>#</th>
        <th>&nbsp;</th>
        <th>ms</th>
        <th>type</th>
        <th>message</th>
      </tr>\n";
      $line_nb = 0;
      foreach($this->log as $logEntry)
      {
        $log = $logEntry->getMessage();

        if ($logEntry->getPriority() >= 6)
        {
          $class = 'Green';
          $priority = 'info';
        }
        else if ($logEntry->getPriority() >= 4)
        {
          $class = 'Orange';
          $priority = 'warning';
        }
        else
        {
          $class = 'Red';
          $priority = 'error';
        }

        $type = preg_replace('/^sf/', '', $logEntry->getType());

        // xdebug information
        $debug_info = '';
        if ($logEntry->getDebugStack())
        {
          $debug_info .= '&nbsp;<a href="#" onclick="Element.toggle(\'debug_'.$line_nb.'\')">'.image_tag($this->base_image_path.'/toggle.gif').'</a><div class="sfStatsDebugInfo" id="debug_'.$line_nb.'" style="display:none">';
          foreach ($logEntry->getDebugStack() as $i => $log_line)
          {
            $debug_info .= '#'.$i.' &raquo; '.$this->formatLogLine('', $log_line).'<br />';
          }
          $debug_info .= "</div>\n";
        }

        // format log
        $log = $this->formatLogLine($type, $log);

        ++$line_nb;
        $format = "<tr class='sfStats%s %s'><td>%s</td><td>%s</td><td>+%s&nbsp;</td><td><span class=\"sfStatsLogType\">%s</span></td><td>%s%s</td></tr>\n";
        $logs .= sprintf($format, $class, $logEntry->getType(), $line_nb, image_tag($this->base_image_path.'/'.$priority.'.png', 'align=middle'), $logEntry->getElapsedTime(), $type, $log, $debug_info);
      }
      $logs .= '</table>';

      $result .= javascript_tag('
      function toggleMessages(myclass)
      {
        elements = document.getElementsByClassName(myclass);
        for (i = 0; i < elements.length; i++)
        {
          Element.toggle(elements[i]);
        }
      }
      ');

      ksort($this->types);
      $types = array();
      foreach ($this->types as $type => $nb)
      {
        $types[] = '<a id="'.$type.'" href="#" onclick="toggleMessages(\''.$type.'\')">'.$type."</a>\n";
      }

      $result .= '
      <div id="sfStatsLogMain" style="display: none">
        <div id="sfStatsLogMenu">
          <div class="float">'.
          implode('&nbsp;-&nbsp;', $types).'&nbsp;&nbsp;
          <a href="#" onclick="toggleMessages(\'sfStatsGreen\')">'.image_tag($this->base_image_path.'/info.png', 'align=middle').'</a>&nbsp;
          <a href="#" onclick="Element.hide(\'sfStatsLogMain\')">'.image_tag($this->base_image_path.'/close.png', 'align=middle').'</a>
          </div>
          <strong>Log messages</strong>
        </div>
        <div id="sfStatsLog">'.$logs.'</div>
      </div>
      ';
    }

    return '<div id="sfStatsBase">'.$result.'</div>';
  }

  private function displayMenu($log_image)
  {
    $result = '<div id="sfStatsRightMenu">';

    if (sfConfig::get('sf_logging_active'))
    {
      $result .= '<a href="#" onclick="Element.show(\'sfStatsLogMain\')">'.image_tag($this->base_image_path.'/'.$log_image.'.png', 'align=middle').'</a>&nbsp;';
    }

    if (sfConfig::get('sf_debug') && sfConfig::get('sf_cache'))
    {
      $self_url = $_SERVER['PHP_SELF'].((!preg_match('/ignore_cache/', $_SERVER['PHP_SELF'])) ? '?ignore_cache=1' : '');
      $result .= '<a href="'.$self_url.'" title="reload and ignore cache">'.image_tag($this->base_image_path.'/reload.png', 'align=middle').'</a>';
    }

    $result .= '
    <a href="#" onclick="Element.hide(\'sfStats'.ucfirst($log_image).'\')">'.image_tag($this->base_image_path.'/close.png', 'align=middle').'</a>
    </div>
    <div id="sfStatsLeftMenu"><a href="#" class="bold" onclick="Element.toggle(\'sfStatsDetails\', \'sfStatsTime\')">symfony</a></div>
    ';

    return $result;
  }

  private function displayCurrentConfig()
  {
    $config = array(
      'debug'        => sfConfig::get('sf_debug')          ? 'on' : 'off',
      'xdebug'       => (function_exists('xdebug_get_function_stack')) ? 'on' : 'off',
      'logging'      => sfConfig::get('sf_logging_active') ? 'on' : 'off',
      'routing'      => sfConfig::get('sf_routing')        ? 'on' : 'off',
      'cache'        => sfConfig::get('sf_cache')          ? 'on' : 'off',
      'eaccelerator' => (function_exists('eaccelerator') && ini_get('eaccelerator.enable')) ? 'on' : 'off',
      'compression'  => sfConfig::get('sf_compressed')     ? 'on' : 'off',
      'tidy'         => (function_exists('tidy_parse_string'))  ? 'on' : 'off',
    );

    $result = '';
    foreach ($config as $key => $value)
    {
      $result .= '<div class="is'.$value.'"><span class="float bold">['.$value.']</span>'.$key.'</div>';
    }

    if (sfConfig::get('sf_debug'))
    {
      // get Propel statistics if available (user created a model and a db)
      // we require Propel here to avoid autoloading and automatic connection
      require_once('propel/Propel.php');
      if (Propel::isInit())
      {
        try
        {
          $con = Propel::getConnection();
          $result .= '<div><span class="float bold">['.$con->getNumQueriesExecuted().']</span>db requests</div>';
        }
        catch (Exception $e)
        {
        }
      }
    }

    return $result;
  }

  public function decorateContentWithDebug($internalUri, $suffix, $retval, $border_color, $bg_color)
  {
    $cache = $this->context->getViewCacheManager();

    if (!sfConfig::get('sf_web_debug'))
    {
      return $retval;
    }

    $last_modified = $cache->lastModified($internalUri, $suffix);
    $id            = md5($internalUri);
    $retval = '
      <div id="main_'.$id.'" class="sfWebDebugActionCache" style="border: 1px solid '.$border_color.'">
      <div id="sub_main_'.$id.'" class="sfStatsCache" style="background-color: '.$bg_color.'; border-right: 1px solid '.$border_color.'; border-bottom: 1px solid '.$border_color.';">
      <div style="height: 16px; padding: 2px"><a href="#" onclick="Element.toggle(\''.$id.'\')"><strong>cache information</strong></a>&nbsp;<a href="#" onclick="Element.hide(\'sub_main_'.$id.'\'); document.getElementById(\'main_'.$id.'\').style.border = \'none\'">'.image_tag($this->base_image_path.'/close.png', 'align=middle').'</a>&nbsp;</div>
        <div style="padding: 2px; display: none" id="'.$id.'">
        [uri]&nbsp;'.$internalUri.'<br />
        [life&nbsp;time]&nbsp;'.$cache->getLifeTime($internalUri, $suffix).'&nbsp;seconds<br />
        [last&nbsp;modified]&nbsp;'.(time() - $last_modified).'&nbsp;seconds<br />
        &nbsp;<br />&nbsp;
        </div>
      </div><div>
      '.$retval.'
      </div></div>
    ';

    return $retval;
  }
}

?>