<?php
/*
 * SPDX-FileCopyrightText: 2024 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Json\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * JSON Body Parser Middleware
 *
 * This middleware parses the JSON body of the request and adds it to the parsed body of the request object.
 * It checks if the request content type is 'application/json' and if the parsed body is empty. If both conditions
 * are met, it decodes the JSON data and adds it to the parsed body of the request.
 */
class JsonBodyParserMiddleware implements MiddlewareInterface
{
    const HANDLED_METHODS = [
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
        RequestMethodInterface::METHOD_OPTIONS,
    ];
    
    
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getMethod(), self::HANDLED_METHODS)
            && in_array('application/json', $request->getHeader('Content-Type'))
            && empty($request->getParsedBody())) {
            $data = [];
            if (in_array('ruga-rugaform', $request->getHeader('X-Ruga-Component'))) {
                foreach (Json::decode(file_get_contents('php://input'), Json::TYPE_ARRAY) as $j) {
                    $data[$j['name']] = $j['value'];
                }
            } else {
                $data = Json::decode(file_get_contents('php://input'), Json::TYPE_ARRAY);
            }
            $request = $request->withParsedBody($data);
        }
        return $handler->handle($request);
    }
    
}