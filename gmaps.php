<?php namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Data\Data;
use Grav\Common\Page\Page;

class GMapsPlugin extends Plugin
{
  private static $instances = 0;

  public static function getSubscribedEvents()
  {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0]
    ];
  }

  public function onPluginsInitialized()
  {
    if ($this->isAdmin()) {
      $this->active = false;
      return;
    }

    $this->enable([
      'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
      'onTwigInitialized'   => ['onTwigInitialized', 0]
      ]);
    }

    public function onTwigInitialized()
    {
      $this->grav['twig']->twig()->addFunction(
      new \Twig_SimpleFunction('gmaps', [$this, 'gmapsFunction'], ['is_safe' => ['html']])
    );
  }

  public function onTwigTemplatePaths()
  {
    $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
  }

  public function gmapsFunction($params = [])
  {
    $page = $this->grav['page'];

    $this->mergeConfig($page, $params);

    $template_js        = 'plugins/gmaps/gmaps.js.twig';
    $template_js_main   = 'plugins/gmaps/gmaps_main.js.twig';
    $template_html      = 'plugins/gmaps/gmaps.html.twig';
    $template_vars      = [
      'id'        => $this->config->get('id') . '-' . self::$instances,
      'width'     => $this->config->get('width'),
      'height'    => $this->config->get('height'),
      'type'      => $this->config->get('type'),
      'class'     => $this->config->get('class'),
      'zoom'      => $this->config->get('zoom'),
      'address'   => $this->config->get('address'),
      'instances' => self::$instances
    ];

    if (0 === self::$instances) {
      $this->grav['assets']->addJs('//maps.googleapis.com/maps/api/js?sensor=false');
      $this->grav['assets']->addInlineJs($this->grav['twig']->twig()->render($template_js_main, $template_vars));
    }

    $this->grav['assets']->addInlineJs($this->grav['twig']->twig()->render($template_js, $template_vars));

    $output = $this->grav['twig']->twig()->render($template_html, $template_vars);

    self::$instances++;

    return $output;
  }

  private function mergeConfig(Page $page, $params = [])
  {
    $this->config = new Data((array) $this->grav['config']->get('plugins.gmaps'));

    if (isset($page->header()->gmaps)) {
      if (is_array($page->header()->gmaps)) {
        $this->config = new Data(array_replace_recursive($this->config->toArray(), $page->header()->gmaps));
      } else {
        $this->config->set('enabled', $page->header()->gmaps);
      }
    }

    $this->config = new Data(array_replace_recursive($this->config->toArray(), $params));
  }
}
