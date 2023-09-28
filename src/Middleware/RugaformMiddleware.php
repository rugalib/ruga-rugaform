<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ruga\Rugaform\DatasourcePlugins\DatasourcePluginManager;
use Ruga\Rugaform\Rugaform;


/**
 * RugaformMiddleware creates a RugaformRequest from a form request and tries to find the desired plugin.
 * If found, the process method is executed and returns a RugaformResponse, which is returned to the client form.
 *
 * @see     RugaformMiddlewareFactory
 */
class RugaformMiddleware implements MiddlewareInterface
{
    /** @var DatasourcePluginManager */
    private $datasourcePluginManager;
    
    
    
    public function __construct(DatasourcePluginManager $datasourcePluginManager)
    {
        $this->datasourcePluginManager = $datasourcePluginManager;
    }
    
    
    
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        \Ruga\Log::functionHead($this);
        
        try {
            $datatablesRequest = new RugaformRequest($request);
            $datasourcePlugin = $this->datasourcePluginManager->get($datatablesRequest->getPluginAlias());
            $datatablesResponse = $datasourcePlugin->process($datatablesRequest);
            
            $jsonEncodingOptions = JsonResponse::DEFAULT_JSON_FLAGS;
            if (!in_array('XMLHttpRequest', $request->getHeader('X-Requested-With'))) {
                $jsonEncodingOptions = $jsonEncodingOptions | JSON_PRETTY_PRINT;
            }
            return new JsonResponse($datatablesResponse, 200, [], $jsonEncodingOptions);
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    Rugaform::ERROR => $e->getMessage(),
                    Rugaform::ERROR_EXCEPTION => get_class($e),
                    Rugaform::ERROR_TRACE => $e->getTrace(),
                    Rugaform::QUERY => null,
                ],
                $e->getCode() == 0 ? 500 : $e->getCode(),
                [],
                JsonResponse::DEFAULT_JSON_FLAGS | JSON_PRETTY_PRINT | JSON_FORCE_OBJECT
            );
        }
    }
    
    
}