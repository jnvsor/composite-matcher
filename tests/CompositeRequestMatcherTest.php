<?php

use CompositeMatcher\CompositeRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class CompositeRequestMatcherTest extends PHPUnit_Framework_TestCase
{
    public function testAddMatcher()
    {
        $rc = new RequestContext();
        $cm = new CompositeRequestMatcher($rc);

        $mock = $this->createMock(UrlMatcher::class);
        $mock
            ->expects($this->once())
            ->method('setContext')
            ->with($this->identicalTo($rc));

        $cm->addMatcher($mock);

        $this->assertAttributeContains($mock, 'request_matchers', $cm);
        $this->assertAttributeNotContains($mock, 'url_matchers', $cm);
        $this->assertAttributeContains($mock, 'context_matchers', $cm);
    }

    public function testSetContext()
    {
        $rc1 = new RequestContext();
        $rc2 = new RequestContext();

        $mock = $this->createMock(UrlMatcher::class);
        $mock
            ->expects($this->exactly(2))
            ->method('setContext')
            ->withConsecutive(
                [$this->identicalTo($rc1)],
                [$this->identicalTo($rc2)]
            );

        $cm = new CompositeRequestMatcher($rc1);
        $cm->addMatcher($mock);
        $cm->setContext($rc2);
    }

    public function testGetContext()
    {
        $rc = new RequestContext();
        $cm = new CompositeRequestMatcher($rc);

        $this->assertSame($rc, $cm->getContext());
    }

    public function testMatch()
    {
        $cm = new CompositeRequestMatcher(new RequestContext());

        $mock = $this->createMock(UrlMatcherInterface::class);
        $cm->addMatcher($mock);

        $mock
            ->expects($this->once())
            ->method('match')
            ->with($this->identicalTo('/'))
            ->willReturn(['1234abcd']);

        $this->assertSame(['1234abcd'], $cm->match('/'));
    }

    public function testMatchRequest()
    {
        $r = new Request();
        $cm = new CompositeRequestMatcher(new RequestContext());

        $mock1 = $this->createMock(RequestMatcherInterface::class);
        $mock1
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->identicalTo($r))
            ->will($this->onConsecutiveCalls(
                $this->returnValue(['1234abcd']),
                $this->throwException(new ResourceNotFoundException())
            ));
        $cm->addMatcher($mock1);

        $mock2 = $this->createMock(RequestMatcherInterface::class);
        $mock2
            ->expects($this->once())
            ->method('matchRequest')
            ->with($this->identicalTo($r))
            ->willReturn(['1234abcd']);
        $cm->addMatcher($mock2);

        $this->assertSame(['1234abcd'], $cm->matchRequest($r));
        $cm->matchRequest($r);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testNoMatchRequest()
    {
        $r = new Request();
        $cm = new CompositeRequestMatcher(new RequestContext());

        $mock = $this->createMock(RequestMatcherInterface::class);
        $cm->addMatcher($mock);

        $mock
            ->expects($this->once())
            ->method('matchRequest')
            ->with($this->identicalTo($r))
            ->will($this->throwException(new ResourceNotFoundException()));

        $cm->matchRequest($r);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testNoMatcher()
    {
        $r = new Request();
        $cm = new CompositeRequestMatcher(new RequestContext());

        $cm->matchRequest($r);
    }

    public function testUrlMatcherFallback()
    {
        $r = new Request();
        $cm = new CompositeRequestMatcher(new RequestContext());

        $url_mock = $this->createMock(UrlMatcherInterface::class);
        $cm->addMatcher($url_mock);

        $url_mock
            ->expects($this->once())
            ->method('match')
            ->with($this->identicalTo('/'))
            ->willReturn(['1234abcd']);

        $request_mock = $this->createMock(RequestMatcherInterface::class);
        $cm->addMatcher($request_mock);

        $request_mock
            ->expects($this->once())
            ->method('matchRequest')
            ->with($this->identicalTo($r))
            ->will($this->throwException(new ResourceNotFoundException()));

        $this->assertSame(['1234abcd'], $cm->matchRequest($r));
    }
}
