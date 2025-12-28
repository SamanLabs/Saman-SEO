<?php

namespace Twiglet;

class Twiglet {

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $filter_descriptions = [];

    public function __construct() {
        $this->register_default_filters();
    }

    public function render(string $template_path, array $vars = []): string {
        if (!is_readable($template_path)) {
            return '';
        }

        $template = file_get_contents($template_path);

        return $this->render_string($template, $vars);
    }

    public function render_string(string $template, array $vars = []): string {
        return preg_replace_callback(
            '/{{\s*(.*?)\s*}}/',
            function ($matches) use ($vars) {
                return $this->evaluate_expression($matches[1], $vars);
            },
            $template
        );
    }

    public function add_filter(string $name, callable $callback, string $description = ''): void {
        $this->filters[$name] = $callback;

        if ($description !== '') {
            $this->filter_descriptions[$name] = $description;
        }
    }

    public function list_filters(): array {
        $info = [];

        foreach ($this->filters as $name => $callback) {
            $info[$name] = $this->filter_descriptions[$name] ?? '';
        }

        return $info;
    }

    protected function evaluate_expression(string $expression, array $vars): string {
        $segments = array_map('trim', explode('|', $expression));
        $value = $this->resolve_variable(array_shift($segments), $vars);

        foreach ($segments as $segment) {
            $value = $this->apply_filter($segment, $value);
        }

        return $this->stringify($value);
    }

    protected function resolve_variable(string $key, array $vars) {
        $parts = explode('.', trim($key));
        $value = $vars;

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return '';
            }
        }

        return $value;
    }

    protected function apply_filter(string $filter, $value) {
        if (preg_match('/^(\w+)\((.*?)\)$/', $filter, $matches)) {
            $name = $matches[1];
            $args = $this->parse_args($matches[2]);
        } else {
            $name = $filter;
            $args = [];
        }

        if (!isset($this->filters[$name])) {
            return $value;
        }

        return call_user_func($this->filters[$name], $value, ...$args);
    }

    protected function parse_args(string $args): array {
        if ($args === '') {
            return [];
        }

        $parts = [];
        $current = '';
        $in_single = false;
        $in_double = false;
        $length = strlen($args);

        for ($i = 0; $i < $length; $i++) {
            $char = $args[$i];

            if ($char === "'" && !$in_double) {
                $in_single = !$in_single;
                $current .= $char;
                continue;
            }

            if ($char === '"' && !$in_single) {
                $in_double = !$in_double;
                $current .= $char;
                continue;
            }

            if ($char === ',' && !$in_single && !$in_double) {
                $parts[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $parts[] = $current;

        return array_map(function ($arg) {
            $arg = trim($arg);

            // Check for quoted strings (compatible with PHP 7.0+)
            if (
                (substr($arg, 0, 1) === '"' && substr($arg, -1) === '"') ||
                (substr($arg, 0, 1) === "'" && substr($arg, -1) === "'")
            ) {
                return substr($arg, 1, -1);
            }

            if (is_numeric($arg)) {
                return $arg + 0;
            }

            return $arg;
        }, $parts);
    }

    protected function stringify($value): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value) || is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    protected function register_default_filters(): void {
        $this->add_filter(
            'truncate',
            function ($value, int $length = 100, string $suffix = '…') {
                $value = trim(strip_tags((string) $value));

                if (function_exists('mb_strlen')) {
                    if (\mb_strlen($value) <= $length) {
                        return $value;
                    }

                    return \mb_substr($value, 0, $length) . $suffix;
                }

                // Fallback if mbstring is not available
                if (strlen($value) <= $length) {
                    return $value;
                }

                return substr($value, 0, $length) . $suffix;
            },
            'Shorten a string to a max length with an optional suffix.'
        );

        $this->add_filter(
            'upper',
            function ($v) {
                return strtoupper((string) $v);
            },
            'Uppercase a string.'
        );
        $this->add_filter(
            'lower',
            function ($v) {
                return strtolower((string) $v);
            },
            'Lowercase a string.'
        );
        $this->add_filter(
            'length',
            function ($v) {
                if (is_string($v)) {
                    return strlen($v);
                }

                if (is_array($v)) {
                    return count($v);
                }

                if ($v instanceof \Countable) {
                    return count($v);
                }

                return 0;
            },
            'Length of a string or array.'
        );
        $this->add_filter(
            'trim',
            function ($v) {
                return trim((string) $v);
            },
            'Trim whitespace from the ends of a string.'
        );
        $this->add_filter(
            'title',
            function ($v) {
                $value = (string) $v;

                if (function_exists('mb_convert_case')) {
                    return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
                }

                return ucwords(strtolower($value));
            },
            'Simple title case.'
        );
        $this->add_filter(
            'split',
            function ($value, string $delimiter = ' ', int $limit = 0) {
                $value = (string) $value;

                if ($limit != 0) {
                    return explode($delimiter, $value, $limit);
                }

                return explode($delimiter, $value);
            },
            'Split a string into an array.'
        );
        $this->add_filter(
            'join',
            function ($value, string $delimiter = '') {
                if (is_array($value)) {
                    return implode($delimiter, $value);
                }

                return (string) $value;
            },
            'Join an array into a string.'
        );

        $this->add_filter(
            'default',
            function ($value, $fallback = '') {
                return $value === '' ? $fallback : $value;
            },
            'Fallback when the value is an empty string.'
        );
    }
}
