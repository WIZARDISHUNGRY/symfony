<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage filter
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfWebDebugFilter extends sfFilter
{
  /**
   * Executes this filter.
   *
   * @param sfFilterChain A sfFilterChain instance
   */
  public function execute($filterChain)
  {
    if (!$this->context->has('sf_web_debug'))
    {
      $webDebug = new sfWebDebug();

      $this->context->set('sf_web_debug', $webDebug);
    }
    else
    {
      $webDebug = $this->context->get('sf_web_debug');
    }

    if ($this->isFirstCall())
    {
      // register sfWebDebug assets
      $webDebug->registerAssets();
    }

    // execute next filter
    $filterChain->execute();

    $response = $this->context->getResponse();

    // don't add debug toolbar:
    // * for XHR requests
    // * if 304
    // * if not rendering to the client
    // * if HTTP headers only
    if (
      $this->context->getRequest()->isXmlHttpRequest() ||
      strpos($response->getContentType(), 'html') === false ||
      $response->getStatusCode() == 304 ||
      $this->context->getController()->getRenderMode() != sfView::RENDER_CLIENT ||
      $response->isHeaderOnly()
    )
    {
      return;
    }

    $content  = $response->getContent();
    $webDebugContent = $webDebug->getResults();

    // add web debug information to response content
    $count = 0;
    $content = str_ireplace('</body>', $webDebugContent.'</body>', $content, $count);
    if (!$count)
    {
      $content .= $webDebugContent;
    }

    $response->setContent($content);
  }
}
