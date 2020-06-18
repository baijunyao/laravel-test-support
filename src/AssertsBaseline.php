<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport;

use File;
use RuntimeException;
use Str;

trait AssertsBaseline
{
    public function assertResponse($response, $headersIgnores = null, $contentIgnores = null)
    {
        $class    = static::class;
        $function = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            ->where('class', $class)
            ->pluck('function')
            ->filter(function ($item) {
                return substr($item, 0, 4) === 'test';
            })
            ->first();

        $defaultHeadersIgnores = [
            'date'       => 'date_ignore',
            'set-cookie' => 'set-cookie_ignore',
        ];

        $defaultContentIgnores = [
            '/<meta name="csrf-token" content=".*">/'          => '<meta name="csrf-token" content="csrf_token_ignore">',
            '/<input type="hidden" name="_token" value=".*">/' => '<input type="hidden" name="_token" value="csrf_token_ignore">',
        ];

        if ($headersIgnores === null) {
            $headersIgnores = $defaultHeadersIgnores;
        } else {
            $headersIgnores += $defaultHeadersIgnores;
        }

        if ($contentIgnores === null) {
            $contentIgnores = $defaultContentIgnores;
        } else {
            $contentIgnores += $defaultContentIgnores;
        }

        $content = preg_replace(array_keys($contentIgnores), array_values($contentIgnores), $response->getContent());

        $statusHeaderContents = [
            'status_code' => $response->getStatusCode(),
            'headers'     => array_merge($response->headers->all(), $headersIgnores),
            'content'     => Str::isJsonArray($content) || Str::isJsonObject($content) ? json_decode($content, true) : $content,
        ];
        $baselinePath  = base_path('tests/Feature/_baseline' . explode('Tests/Feature', str_replace('\\', '/', $class))[1]);
        $baselineFile  = $baselinePath . '/' . Str::camel(substr($function, 4)) . '.json';
        $do_rebase     = array_search('rebase', $_SERVER['argv'], true) !== false;

        if (!File::isFile($baselineFile) && $do_rebase === false) {
            throw new RuntimeException("Test baseline data for $class::$function is not found, use '-d rebase' argument to create the baseline data.");
        } elseif ($do_rebase) {
            if (!File::isDirectory($baselinePath)) {
                File::makeDirectory($baselinePath, 0755, true);
            }

            File::put($baselineFile, json_encode($statusHeaderContents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo 'R';
        } else {
            $expectation = json_decode(File::get($baselineFile), true);

            foreach ($statusHeaderContents as $key => $statusHeaderContent) {
                $this->assertEquals($expectation[$key], $statusHeaderContent);
            }
        }
    }

    public function getRoute()
    {
        return Str::plural(
            Str::camel(
                str_replace('ControllerTest', '', class_basename(static::class))
            )
        );
    }
}
