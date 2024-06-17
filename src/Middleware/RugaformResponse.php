<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Ruga\Rugaform\Rugaform;

class RugaformResponse implements \JsonSerializable
{
    /** @var RugaformRequest */
    private RugaformRequest $request;
    
    /** @var array Current data after the operation. */
    private array $data = [];
    
    /** @var string|null Unique ID of the row. NULL, if new row. */
    private ?string $uniqueid = null;
    
    /** @var string Executed query/method. */
    private string $query = '';
    
    /** @var array Log messages. */
    private array $messages = [];
    
    /** @var string Final message representing the operation's outcome. */
    private string $finalMessage = '';
    
    /** @var string|null Final message severity. */
    private ?string $finalSeverity = null;
    
    /** @var string|null Final message type (STATUS|RESULT). */
    private ?string $finalType = null;
    
    /** @var string|null The URI to be called after a successful operation. */
    private ?string $successUri = null;
    
    /** @var int Delay in ms before the form reloads or switches to a new page. */
    private int $redirectionDelay = 0;
    
    
    
    public function __construct(RugaformRequest $request)
    {
        $this->request = $request;
    }
    
    
    
    public function getRugaformRequest(): RugaformRequest
    {
        return $this->request;
    }
    
    
    
    public function getData(): array
    {
        return $this->data;
    }
    
    
    
    public function setData(array $data)
    {
        $this->data = $data;
    }
    
    
    
    public function getUniqueid()
    {
        return $this->uniqueid;
    }
    
    
    
    public function setUniqueid($uniqueid)
    {
        $this->uniqueid = $uniqueid;
    }
    
    
    
    public function addMessage(string $msg)
    {
        $this->messages[] = $msg;
    }
    
    
    
    public function setFinalMessage(string $msg, string $severity, string $type)
    {
        $this->addMessage($msg);
        $this->finalMessage = $msg;
        $this->finalSeverity = $severity;
        $this->finalType = $type;
    }
    
    
    
    /**
     * Set the success URI and redirection delay.
     *
     * @param string $uri              The success URI.
     * @param int    $redirectionDelay The delay in milliseconds before redirecting to the success URI. Default value
     *                                 is 3000 (3 seconds).
     *
     * @return void
     */
    public function setSuccessUri(string $uri, int $redirectionDelay = 3000)
    {
        $this->successUri = $uri;
        $this->setRedirectionDelay($redirectionDelay);
    }
    
    
    
    /**
     * Get the success URI.
     *
     * @return string|null The success URI, or null if not set.
     */
    public function getSuccessUri(): string
    {
        return $this->successUri;
    }
    
    
    
    public function setQuery(string $query)
    {
        \Ruga\Log::functionHead();
        $this->query = $query;
    }
    
    
    
    public function getQuery(): string
    {
        return $this->query;
    }
    
    
    
    /**
     * Get the redirection delay.
     *
     * @return int The redirection delay in seconds.
     * @see RugaformResponse::setRedirectionDelay())
     */
    public function getRedirectionDelay(): int
    {
        return $this->redirectionDelay;
    }
    
    
    
    /**
     * Set the redirection delay for the object. This is the delay before a form is reloaded or the browser switches
     * to a new page after form submitted successfully.
     *
     * @param int $redirectionDelay The redirection delay to set in milliseconds.
     *
     * @return void
     */
    public function setRedirectionDelay(int $redirectionDelay): void
    {
        $this->redirectionDelay = $redirectionDelay;
    }
    
    
    
    public function setFilter(array $filter)
    {
        $this->filter = $filter;
    }
    
    
    
    public function getFilter(): array
    {
        return $this->filter;
    }
    
    
    
    /**
     * Specify data which should be serialized to JSON
     *
     * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        $uniqueid = Rugaform::UNIQUEID;
        $favourite = Rugaform::FAVOURITE;
        $success_uri = Rugaform::SUCCESS_URI;
        $redirectionDelay = Rugaform::REDIRECTION_DELAY;
        $result = Rugaform::RESULT;
        $error = Rugaform::ERROR;
        $query = Rugaform::QUERY;
        $data = Rugaform::DATA;
        
        $o = new \stdClass();
        
        $o->$query = $this->getQuery(); // Optional: Show query to the developer
        $o->$result = null;
        $o->$error = null; // Optional: If an error occurs during the running of the server-side processing script
        $o->$success_uri = null;
        $o->$redirectionDelay = null;
        $o->$uniqueid = null;
        $o->$favourite = null; // The data to be displayed in the form.
        $o->$data = null; // The data to be displayed in the form.
        
        try {
            $o->$data = $this->getData();
            $o->$success_uri = $this->getSuccessUri() ?? '';
            $o->$redirectionDelay = $this->getRedirectionDelay();
            $o->$uniqueid = $this->getUniqueid();
            $o->$result = [
                'finalMessage' => $this->finalMessage,
                'finalSeverity' => $this->finalSeverity,
                'finalType' => $this->finalType,
                'messages' => $this->messages,
            ];
            if ($this->finalSeverity == 'ERROR') {
                $o->$error = $this->finalMessage;
            }
        } catch (\Exception $e) {
            $o->$error = $e->getMessage();
        }

//        file_put_contents('tmp/DatatablesResponse.json', json_encode($o, JSON_PRETTY_PRINT));
        
        return $o;
    }
}