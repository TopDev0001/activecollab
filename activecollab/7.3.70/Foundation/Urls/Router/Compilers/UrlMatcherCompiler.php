<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Compilers;

use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;
use ActiveCollab\Foundation\Urls\Router\Route;
use Angie\Inflector;

class UrlMatcherCompiler implements CompilerInterface
{
    public function compile(RouteMapperInterface $mapper, string $file_path): void
    {
        $match_file = new CompiledFile();

        $match_file->writeLine('<?php');
        $match_file->writeLine();
        $match_file->writeLine('/*');
        $match_file->writeLine(' * This file is part of the ActiveCollab project.');
        $match_file->writeLine(' *');
        $match_file->writeLine(' * (c) A51 doo <info@activecollab.com>. All rights reserved.');
        $match_file->writeLine(' */');
        $match_file->writeLine();
        $match_file->writeLine('declare(strict_types=1);');
        $match_file->writeLine();
        $match_file->writeLine('namespace ActiveCollab\Foundation\Compile;');
        $match_file->writeLine();
        $match_file->writeLine('use ActiveCollab\Foundation\Urls\Router\UrlMatcher\CompiledUrlMatcher as BaseCompiledUrlMatcher;');
        $match_file->writeLine('use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedCollection;');
        $match_file->writeLine('use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedEntity;');
        $match_file->writeLine('use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRoute;');
        $match_file->writeLine('use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRouteInterface;');
        $match_file->writeLine();
        $match_file->writeLine('class CompiledUrlMatcher extends BaseCompiledUrlMatcher');
        $match_file->writeLine('{');
        $match_file->writeLine('    protected function matchRouteFrom(string $path, string $query_string): ?MatchedRouteInterface');
        $match_file->writeLine('    {');
        $match_file->writeLine('        $matches = null;');
        $match_file->writeLine();

        /** @var Route[] $routes */
        $routes = array_reverse($mapper->getRoutes());

        $counter = 0;
        foreach ($routes as $route_name => $route) {
            ++$counter;

            if ($counter == 1) {
                $match_file->writeLine('        if (preg_match(' . var_export($route->getRegularExpression(), true) . ', $path, $matches)) {');
            } else {
                $match_file->writeLine('        } elseif (preg_match(' . var_export($route->getRegularExpression(), true) . ', $path, $matches)) {');
            }

            $match_file->writeLine('            $url_params = $this->valuesFromMatchedPath(');
            $match_file->writeLine(
                sprintf(
                    '                %s,',
                    $this->exportArray(
                        $route->getNamedParameters(),
                        '                    ',
                        true
                    )
                )
            );
            $match_file->writeLine(
                sprintf(
                    '                %s,',
                    $this->exportArray(
                        $route->getDefaults(),
                        '                    '
                    )
                )
            );
            $match_file->writeLine('                $matches,');
            $match_file->writeLine('                $query_string');
            $match_file->writeLine('            );');
            $match_file->writeLine();

            if ($mapper->isMappedResource($route_name)) {
                $matched_route_class = 'MatchedCollection';
            } elseif ($mapper->isMappedEntity($route_name)) {
                $matched_route_class = 'MatchedEntity';
            } else {
                $matched_route_class = 'MatchedRoute';
            }

            $match_file->writeLine(sprintf('            return new %s(', $matched_route_class));
            $match_file->writeLine(sprintf('                %s, ', var_export($route->getName(), true)));

            if ($matched_route_class === 'MatchedCollection') {
                $match_file->writeLine('                $url_params,');
                $match_file->writeLine(
                    sprintf('                %s', var_export(Inflector::camelize($route_name), true))
                );
            } elseif ($matched_route_class === 'MatchedEntity') {
                $match_file->writeLine('                $url_params,');
                $match_file->writeLine(
                    sprintf('                %s,', var_export(Inflector::camelize($route_name), true))
                );
                $match_file->writeLine(sprintf('                $url_params[\'%s_id\'] ?? 0', $route_name));
            } else {
                $match_file->writeLine('                $url_params');
            }

            $match_file->writeLine('            );');
        }

        $match_file->writeLine('        }');
        $match_file->writeLine();
        $match_file->writeLine('        return null;');
        $match_file->writeLine('    }');

        $match_file->writeLine('}');

        $match_file->save($file_path);
    }

    private function exportArray(array $array, string $indent, bool $values_only = false): string
    {
        if (empty($array)) {
            return '[]';
        }

        $result = "[\n";

        if ($values_only) {
            foreach ($array as $v) {
                $result .= sprintf(
                    "%s    %s,\n",
                    $indent,
                    is_array($v)
                        ? $this->exportArray($v, $indent . '    ', $values_only)
                        : var_export($v, true)
                );
            }
        } else {
            foreach ($array as $k => $v) {
                $result .= sprintf(
                    "%s    %s => %s,\n",
                    $indent,
                    var_export($k, true),
                    is_array($v)
                        ? $this->exportArray($v, $indent . '    ', $values_only)
                        : var_export($v, true)
                );
            }
        }

        $result .= $indent . ']';

        return $result;
    }
}
