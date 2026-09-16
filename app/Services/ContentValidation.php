<?php

namespace App\Services;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\InputBag;

class ContentValidation
{
    /** @param class-string<FormRequest> $requestClass @param array<string,mixed> $data @param array<string,mixed> $parameters @return array<string,mixed> */
    public function validate(string $requestClass, array $data, array $parameters = []): array
    {
        $request = $requestClass::createFrom(request(), new $requestClass);
        $request->setJson(new InputBag);
        $request->files->replace([]);
        $request->replace($data);
        $route = new Route('POST', 'content-validation', fn (): null => null);
        $route->bind($request);
        foreach ($parameters as $name => $value) {
            $route->setParameter($name, $value);
        }
        $request->setRouteResolver(fn (): Route => $route);
        $request->setUserResolver(fn (): mixed => auth()->user());
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();

        return $request->validated();
    }
}
