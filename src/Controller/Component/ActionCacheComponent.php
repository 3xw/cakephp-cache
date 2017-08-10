<?php
namespace Awallef\Cache\Controller\Component;

use Awallef\Cache\Middleware\ResponseCacheMiddleware;
use Cake\Cache\Cache;
use Cake\Http\Response;
use Cake\Event\Event;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component;

/**
* Cache component
*/
class ActionCacheComponent extends Component
{
  protected $_controller;

  protected $_defaultConfig = [];

  public function __construct(ComponentRegistry $collection, $config = [])
  {
    $this->_controller = $collection->getController();
    parent::__construct($collection, $config);
  }

  public function startup(Event $event)
  {
    $rcm = new ResponseCacheMiddleware();
    $response = new Response();
    if(!empty($this->_controller->request->params['_ext'])) $response->type($this->_controller->request->params['_ext']);
    $response->statusCode('200');
    $rule = $rcm->checkRules($this->_controller->request, $response);

    // if no rule exit
    if(empty($rule)) return true;

    // look for a cache response
    if(!$rule['clear'] && !$rule['skip'])
    {
      $body = Cache::read($this->_controller->request->here(), $rule['cache']);

      // no cache found
      if(empty($body)) return true;

      // cache found !
      $response->body($body);
      return $response;
    }

    // no cache
    return true;
  }
}
