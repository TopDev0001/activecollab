<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Compilers;

use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;

class UrlAssemblerCompiler implements CompilerInterface
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
        $match_file->writeLine('use ActiveCollab\Foundation\Urls\Router\UrlAssembler\UrlAssembler;');
        $match_file->writeLine();
        $match_file->writeLine('class CompiledUrlAssembler extends UrlAssembler');
        $match_file->writeLine('{');
        $match_file->writeLine('    protected function getRouteAssemblyParts(string $route_name): array');
        $match_file->writeLine('    {');
        $match_file->writeLine('        switch ($route_name) {');

        foreach ($mapper->getRoutes() as $route_name => $route) {
            $match_file->writeLine(sprintf('            case %s:', var_export($route_name, true)));
            $match_file->writeLine('                return [');
            $match_file->writeLine(
                sprintf('                    %s,', var_export($route->getRouteString(), true))
            );
            $match_file->writeLine(
                sprintf(
                    '                    %s,',
                    $this->exportArray($route->getDefaults(), '                    ')
                )
            );
            $match_file->writeLine('                ];');
        }

        $match_file->writeLine('        }');
        $match_file->writeLine();
        $match_file->writeLine('        return [');
        $match_file->writeLine('            null,');
        $match_file->writeLine('            null,');
        $match_file->writeLine('        ];');
        $match_file->writeLine('    }');
        $match_file->writeLine('}');
        $match_file->writeLine();

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
