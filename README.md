# CompositeMatcher

Provides a "Composite matcher" for `symfony/routing` that takes multiple matchers and runs them in sequence.

Useful for duplicating overly-complex legacy routing systems.

## Example using Silex

```php
<?php

use Silex\Provider\Routing\RedirectableUrlMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

include 'vendor/autoload.php';

$app = new Silex\Application();

// Change the request matcher to a CompositeMatcher of
// the Silex matcher and our own CatchLeftoversMatcher
$app['request_matcher'] = function ($app) {
    $ret = new CompositeMatcher\CompositeRequestMatcher($app['request_context']);
    $ret->addMatcher(new RedirectableUrlMatcher($app['routes'], $app['request_context']));
    $ret->addMatcher(new CatchLeftoversMatcher());

    return $ret;
};

$app->get('/', function(){
    return 'woot?';
});

$app->run();

class CatchLeftoversMatcher implements RequestMatcherInterface {
    public function matchRequest(Request $r) {
        if ($r->getPathInfo() != '/test/') {
            throw new ResourceNotFoundException();
        }

        return [
            'test' => 'test',
            'foo' => 'bar',
            '_controller' => function(){
                return 'waat?';
            },
        ];
    }
}
```

## Requirements

Should work down to 5.4, but I'm only running CI on 5.6+

Depends on `symfony/http-foundation` and `symfony/routing`
