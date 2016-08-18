<?php
namespace AppBundle;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionRequestProcessor
{
    private $session;
    private $token;
    private $requestStack;

    public function __construct(Session $session, RequestStack $requestStack)
    {
        $this->session = $session;
        $this->requestStack = $requestStack;
    }

    public function processRecord(array $record)
    {
        if (null === $this->token) {
            try {
                $this->token = substr($this->session->getId(), 0, 8);
            } catch (\RuntimeException $e) {
                $this->token = '????????';
            }
            $this->token .= '-' . substr(uniqid(), -8);
        }
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest !== null) {
            $record['extra']['ip'] = $currentRequest->getClientIp();
        } else {
            $record['extra']['ip'] = 'n/a';
        }

        $record['extra']['token'] = $this->token;
        $record['extra']['memory'] = round(memory_get_usage()/1000000);

        return $record;
    }
}
