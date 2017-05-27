<?php

namespace CompositeMatcher;

use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\HttpFoundation\Request;

class CompositeRequestMatcher implements UrlMatcherInterface, RequestMatcherInterface
{
    protected $context;
    protected $url_matchers = [];
    protected $request_matchers = [];
    protected $context_matchers = [];

    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function addMatcher($matcher)
    {
        if ($matcher instanceof RequestMatcherInterface) {
            $this->request_matchers[] = $matcher;
        } elseif ($matcher instanceof UrlMatcherInterface) {
            $this->url_matchers[] = $matcher;
        } else {
            throw new \InvalidArgumentException(__METHOD__.' only accepts UrlMatcherInterface and RequestMatcherInterface');
        }

        if ($matcher instanceof RequestContextAwareInterface) {
            $this->context_matchers[] = $matcher;
            $matcher->setContext($this->context);
        }
    }

    public function match($pathinfo)
    {
        $error = null;

        foreach ($this->url_matchers as $matcher) {
            try {
                return $matcher->match($pathinfo);
            } catch (MethodNotAllowedException $e) {
                $error = $e;
            } catch (ResourceNotFoundException $e) {
                $error = $e;
            }
        }

        if ($error) {
            throw $error;
        } else {
            throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
        }
    }

    public function matchRequest(Request $request)
    {
        $error = null;

        foreach ($this->request_matchers as $matcher) {
            try {
                return $matcher->matchRequest($request);
            } catch (MethodNotAllowedException $e) {
                $error = $e;
            } catch (ResourceNotFoundException $e) {
                $error = $e;
            }
        }

        return $this->match($request->getPathInfo());
    }

    public function setContext(RequestContext $context)
    {
        $this->context = $context;

        foreach ($this->context_matchers as $matcher) {
            $matcher->setContext($this->context);
        }
    }

    public function getContext()
    {
        return $this->context;
    }
}
