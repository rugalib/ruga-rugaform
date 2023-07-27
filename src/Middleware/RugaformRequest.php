<?php

declare(strict_types=1);

namespace Ruga\Rugaform\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Ruga\Rugaform\Rugaform;

/**
 * Class RugaformRequest
 */
class RugaformRequest
{
    /** @var ServerRequestInterface */
    private ServerRequestInterface $request;
    
    /** @var array Submitted data for the operation. */
    private array $data = [];
    
    
    
    public function __construct(ServerRequestInterface $request)
    {
        // Store data in request, if method is DELETE
        // PHP and Laminas do not do this for DELETE
        if($request->getMethod() == RequestMethodInterface::METHOD_DELETE) {
            $data=[];
            parse_str($request->getBody()->getContents(), $data);
            $this->request = $request->withParsedBody($data);
        } else {
            $this->request = $request;
        }
        
        if ($this->request->getMethod() == RequestMethodInterface::METHOD_GET) {
            $this->data = (array)$this->request->getQueryParams() ?? null;
        } else {
            $this->data = (array)$this->request->getParsedBody() ?? null;
        }
    }
    
    
    
    public function getData(): array
    {
        return $this->data;
    }
    
    
    
    public function getUniqueid()
    {
        return $this->data[Rugaform::UNIQUEID] ?? null;
    }
    
    public function getSuccessUri(): UriInterface
    {
        return $this->getRequest()->getUri();
    }
    
    /**
     * Return an array containing all the path components.
     *
     * @return array
     */
    public function getRequestPathParts(): array
    {
        $uriPath = trim($this->request->getUri()->getPath(), " /\\");
        return explode('/', $uriPath);
    }
    
    
    
    /**
     * Returns the alias name of the desired datasource plugin.
     *
     * @return string
     */
    public function getPluginAlias(): string
    {
        return $this->getRequestPathParts()[0] ?? '';
    }
    
    
    
    /**
     * Returns the original request.
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
    
    
    
    /**
     * Return true, if current data for the form is requested.
     *
     * @return bool
     */
    public function isFormGetRequest(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_GET);
    }
    
    
    
    /**
     * Return true, if the request asks to delete the row.
     *
     * @return bool
     */
    public function isFormDeleteRequest(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_DELETE);
    }
    
    
    
    /**
     * Return true, if the request asks to set the favourite value.
     *
     * @return bool
     */
    public function isFormSetFavourite(): bool
    {
        return (($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_POST)
                || ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_PUT))
            && array_key_exists(Rugaform::FAVOURITE, $this->data);
    }
    
    
    
    /**
     * Return true, if the request asks to create a row.
     *
     * @return bool
     */
    public function isFormCreateRow(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_POST) && !$this->isFormSetFavourite(
            );
    }
    
    
    
    /**
     * Return true, if the request asks to update a row.
     *
     * @return bool
     */
    public function isFormUpdateRow(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_PUT) && !$this->isFormSetFavourite();
    }
    
    
}