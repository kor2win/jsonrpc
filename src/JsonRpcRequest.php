<?php

namespace Tochka\JsonRpc;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\Helpers\LogHelper;
use Tochka\JsonRpc\Middleware\BaseMiddleware;

class JsonRpcRequest
{
    public $call;

    public $id;
    public $controller;
    public $method;
    public $params = [];

    public $service = 'guest';

    /** @var JsonRpcServer */
    public $server;

    public function __construct(\StdClass $call, $server)
    {
        $this->call = $call;
        $this->server = $server;
        $this->id = !empty($call->id) ? $call->id : null;
    }

    /**
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle()
    {
        $middlewareList = $this->server->middleware;

        foreach ($middlewareList as $className) {
            /** @var BaseMiddleware $middleware */
            $middleware = new $className();
            $middleware->handle($this);
        }

        if (empty($this->controller) || empty($this->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }

        $logContext = [
            'method'  => $this->call->method,
            'call'    => class_basename($this->controller) . '::' . $this->method,
            'id'      => $this->id,
            'service' => $this->service,
        ];

        $request = ArrayHelper::fromObject($this->call);
        $logRules = Config::get('jsonrpc.log.hideParams', []);
        $globalRules = $logRules['*'] ?? [];
        $controllerRules = $logRules[get_class($this->controller)] ?? [];
        $methodRules = $logRules[get_class($this->controller) . '@' . $this->method] ?? [];
        $rules = array_merge($globalRules, $controllerRules, $methodRules);
        $request['params'] = LogHelper::hidePrivateData($request['params'] ?? [], $rules);

        Log::channel(Config::get('jsonrpc.log.channel', 'default'))
            ->info('New request', $logContext + ['request' => $request]);

        $result = $this->controller->{$this->method}(...$this->params);

        Log::channel(Config::get('jsonrpc.log.channel', 'default'))
            ->info('Successful request', $logContext);

        return $result;
    }
}
