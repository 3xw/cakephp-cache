<?
namespace Awallef\Cache\Middleware;

use Cake\Cache\Cache;
use Cake\Log\Log;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
//use Cake\Network\Request;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

class ResponseCacheMiddleware
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
      'settings' => [],
      'rules' => [],
  ];

  protected function _init()
  {
    if(empty(Configure::read('Trois.cache.settings')) && empty(Configure::read('Trois.cache.rules')))
    {
      $key = 'cache';
      try {
        Configure::load($key, 'default');
      } catch (Exception $ex) {
        throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', $key), 1);
      }
    }
    $this->config('settings', Configure::read('Trois.cache.settings'));
    $this->config('rules', Configure::read('Trois.cache.rules'));
  }

  public function __invoke($request, $response, $next)
  {
    $response = $next($request, $response);
    $this->_init();
    $this->_execRule($request, $response);

    return $response;
  }

  public function checkRules($request, $response)
  {
    $this->_init();
    return _checkRules($request, $response);
  }

  protected function _execRule($request, $response)
  {
    $rule = $this->_checkRules($request, $response);
    if($rule){
      if($rule['clear'] && !$rule['skip']){
        if(is_array($rule['key'])){
          foreach($rule['key'] as $key){
            Cache::delete($key, $rule['cache']);
          }
        }else{
          Cache::delete($rule['key'], $rule['cache']);
        }
      }else if(!$rule['clear'] && !$rule['skip']){
        if(is_array($rule['key'])){
          foreach($rule['key'] as $key){
            Cache::write($key, $response->body(), $rule['cache']);
          }
        }else{
          Cache::write($rule['key'], $response->body(), $rule['cache']);
        }
      }
    }
  }

  protected function _compress($out)
  {
    return preg_replace(array('/<!--(.*)-->/Uis',"/[[:blank:]]+/"),array('',' '),str_replace(array("\n","\r","\t"),'',$out));
  }

  protected function _checkRules($request, $response)
  {
    $rules = $this->config('rules');
    foreach ($rules as $rule) {
      $rule = $this->_matchRule($rule, $request, $response);
      if ($rule !== null) {
        return $rule;
      }
    }
    return false;
  }

  protected function _matchRule($rule, $request, $response)
  {
    $method = $request->getMethod();
    $plugin = $request->plugin;
    $controller = $request->controller;
    $action = $request->action;
    $code = $response->statusCode();
    $prefix = null;
    $extension = null;
    if (!empty($request->params['prefix'])) {
      $prefix = $request->params['prefix'];
    }
    if (!empty($request->params['_ext'])) {
      $extension = $request->params['_ext'];
    }

    if ($this->_matchOrAsterisk($rule, 'method', $method, true) &&
    $this->_matchOrAsterisk($rule, 'code', $code, true) &&
    $this->_matchOrAsterisk($rule, 'prefix', $prefix, true) &&
    $this->_matchOrAsterisk($rule, 'plugin', $plugin, true) &&
    $this->_matchOrAsterisk($rule, 'extension', $extension, true) &&
    $this->_matchOrAsterisk($rule, 'controller', $controller) &&
    $this->_matchOrAsterisk($rule, 'action', $action)) {

      $rule = [
        'skip' => Hash::get($rule, 'skip'),
        'cache' => Hash::get($rule, 'cache'),
        'clear' => Hash::get($rule, 'clear'),
        'key' => Hash::get($rule, 'key'),
        'compress' => Hash::get($rule, 'compress'),
      ];
      foreach($rule as $key => &$value){
        $value = $this->_getRuleBoolProperty($request, $rule, $key);
      }
      return $rule;
    }
    return null;
  }

  protected function _getRuleBoolProperty($request, $rule, $key)
  {
    $prop = $rule[$key];
    if ($prop === null) {
      //clear will be true by default
      return ($key == 'cache')? $this->config('settings')['default']: (($key == 'key')? $request->here():false);
    } elseif (is_callable($prop)) {
      return call_user_func($prop,$request);
    } else {
      return $prop;
    }
  }

  protected function _matchOrAsterisk($permission, $key, $value, $allowEmpty = false)
  {
    $possibleValues = (array)Hash::get($permission, $key);
    if ($allowEmpty && empty($possibleValues) && $value === null) {
      return true;
    }
    if (Hash::get($permission, $key) === '*' ||
    in_array($value, $possibleValues) ||
    in_array(Inflector::camelize($value, '-'), $possibleValues)) {
      return true;
    }

    return false;
  }

}
