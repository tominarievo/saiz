<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * LaravelCollective/HTML の代替となる軽量フォームビルダー。
 * 既存 Blade の Form:: 呼び出しを維持したまま、廃止パッケージへの依存を外す。
 */
class Form
{
    /** @var mixed */
    protected static $model = null;

    public static function model($model, array $options = [])
    {
        static::$model = $model;

        return static::open($options);
    }

    public static function open(array $options = [])
    {
        $route = $options['route'] ?? null;
        $routeName = null;
        $routeParameters = [];

        if (is_array($route)) {
            $routeName = $route[0] ?? null;
            $routeParameters = array_slice($route, 1);
        } elseif (is_string($route)) {
            $routeName = $route;
        }

        $action = static::resolveAction($options, $routeName, $routeParameters);
        $method = strtoupper($options['method'] ?? static::inferMethod($routeName) ?? 'POST');

        $attributes = array_diff_key($options, array_flip([
            'method', 'route', 'url', 'action', 'files',
        ]));

        $html = '<form method="' . ($method === 'GET' ? 'GET' : 'POST') . '" action="' . e($action) . '" accept-charset="UTF-8"';

        if (!empty($options['files'])) {
            $html .= ' enctype="multipart/form-data"';
        }

        $html .= static::attributes($attributes) . '>';

        if ($method !== 'GET') {
            $html .= csrf_field();

            if ($method !== 'POST') {
                $html .= method_field($method);
            }
        }

        return new HtmlString($html);
    }

    public static function close()
    {
        static::$model = null;

        return new HtmlString('</form>');
    }

    public static function text($name, $value = null, array $options = [])
    {
        return static::input('text', $name, static::value($name, $value), $options);
    }

    public static function email($name, $value = null, array $options = [])
    {
        return static::input('email', $name, static::value($name, $value), $options);
    }

    public static function password($name, array $options = [])
    {
        return static::input('password', $name, null, $options);
    }

    public static function hidden($name, $value = null, array $options = [])
    {
        return static::input('hidden', $name, static::value($name, $value), $options);
    }

    public static function file($name, array $options = [])
    {
        return static::input('file', $name, null, $options);
    }

    public static function submit($value = null, array $options = [])
    {
        return static::input('submit', null, $value, $options);
    }

    public static function button($value = null, array $options = [])
    {
        $options = array_merge(['type' => 'button'], $options);

        return new HtmlString('<button' . static::attributes($options) . '>' . e($value) . '</button>');
    }

    public static function label($value = null, $for = null, array $options = [])
    {
        if ($for !== null) {
            $options['for'] = $for;
        }

        return new HtmlString('<label' . static::attributes($options) . '>' . e($value) . '</label>');
    }

    public static function textarea($name, $value = null, array $options = [])
    {
        $options['name'] = $name;
        $value = static::value($name, $value);

        return new HtmlString(
            '<textarea' . static::attributes($options) . '>' . e($value) . '</textarea>'
        );
    }

    public static function checkbox($name, $value = 1, $checked = null, array $options = [])
    {
        $options['type'] = 'checkbox';
        $options['name'] = $name;
        $options['value'] = $value;

        if (static::isChecked($name, $value, $checked)) {
            $options['checked'] = 'checked';
        }

        return new HtmlString('<input' . static::attributes($options) . '>');
    }

    public static function radio($name, $value = null, $checked = null, array $options = [])
    {
        $options['type'] = 'radio';
        $options['name'] = $name;
        $options['value'] = $value;

        if (static::isChecked($name, $value, $checked)) {
            $options['checked'] = 'checked';
        }

        return new HtmlString('<input' . static::attributes($options) . '>');
    }

    public static function select($name, $list = [], $selected = null, array $options = [])
    {
        $placeholder = $options['placeholder'] ?? null;
        unset($options['placeholder']);

        $options['name'] = $name;

        if (!empty($options['multiple']) && substr($name, -2) !== '[]') {
            $options['name'] = $name . '[]';
        }

        $selected = static::selectedValue($name, $selected);
        $selectedValues = is_array($selected)
            ? array_map('strval', $selected)
            : [(string) $selected];

        $html = '<select' . static::attributes($options) . '>';

        if ($placeholder !== null) {
            $html .= '<option value="">' . e($placeholder) . '</option>';
        }

        foreach (static::toArray($list) as $value => $label) {
            if (is_array($label)) {
                $html .= '<optgroup label="' . e($value) . '">';
                foreach ($label as $groupValue => $groupLabel) {
                    $html .= static::option((string) $groupValue, $groupLabel, $selectedValues);
                }
                $html .= '</optgroup>';
                continue;
            }

            $html .= static::option((string) $value, $label, $selectedValues);
        }

        $html .= '</select>';

        return new HtmlString($html);
    }

    public static function inputError($name)
    {
        if (!session()->has('errors')) {
            return new HtmlString('');
        }

        $errors = session('errors');

        if (!$errors->has($name)) {
            return new HtmlString('');
        }

        $message = e($errors->first($name));
        $selector = preg_replace('/[^A-Za-z0-9_\[\]-]/', '', $name);

        return new HtmlString(
            '<span class="error invalid-feedback" style="display: block">' . $message . '</span>' .
            '<script>$(\'input[name="' . $selector . '"], select[name="' . $selector . '"], textarea[name="' . $selector . '"]\').addClass(\'is-invalid\');</script>'
        );
    }

    protected static function input($type, $name, $value, array $options)
    {
        $options['type'] = $type;

        if ($name !== null) {
            $options['name'] = $name;
        }

        if ($value !== null) {
            $options['value'] = $value;
        }

        return new HtmlString('<input' . static::attributes($options) . '>');
    }

    protected static function resolveAction(array $options, $routeName, array $routeParameters)
    {
        if ($routeName !== null) {
            if (count($routeParameters) === 1 && array_key_exists(0, $routeParameters)) {
                return route($routeName, $routeParameters[0]);
            }

            return route($routeName, $routeParameters);
        }

        if (isset($options['url'])) {
            return $options['url'];
        }

        if (isset($options['action'])) {
            return $options['action'];
        }

        return url()->current();
    }

    protected static function inferMethod($routeName)
    {
        if ($routeName === null) {
            return null;
        }

        $route = app('router')->getRoutes()->getByName($routeName);

        if (!$route) {
            return null;
        }

        $methods = array_map('strtoupper', $route->methods());

        if (in_array('GET', $methods, true)) {
            return 'GET';
        }

        if (in_array('PUT', $methods, true)) {
            return 'PUT';
        }

        if (in_array('PATCH', $methods, true)) {
            return 'PATCH';
        }

        if (in_array('DELETE', $methods, true)) {
            return 'DELETE';
        }

        return 'POST';
    }

    protected static function value($name, $value)
    {
        $old = old($name);

        if ($old !== null) {
            return $old;
        }

        if ($value !== null) {
            return $value;
        }

        return static::modelValue($name);
    }

    protected static function selectedValue($name, $selected)
    {
        $old = old($name);

        if ($old !== null) {
            return $old;
        }

        if ($selected !== null) {
            return $selected;
        }

        return static::modelValue($name);
    }

    protected static function modelValue($name)
    {
        if (static::$model === null) {
            return null;
        }

        $key = str_replace('[]', '', $name);

        return data_get(static::$model, $key);
    }

    protected static function isChecked($name, $value, $checked)
    {
        $old = old($name);

        if ($old !== null) {
            $current = $old;
        } elseif ($checked !== null) {
            $current = $checked;
        } else {
            $current = static::modelValue($name);
        }

        if (is_array($current)) {
            return in_array((string) $value, array_map('strval', $current), true);
        }

        if (is_bool($current)) {
            return $current;
        }

        return (string) $current === (string) $value || ($current == true && $value == true);
    }

    protected static function option($value, $label, array $selectedValues)
    {
        $selected = in_array((string) $value, $selectedValues, true) ? ' selected' : '';

        return '<option value="' . e($value) . '"' . $selected . '>' . e($label) . '</option>';
    }

    protected static function attributes(array $attributes)
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            if ($value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $html .= ' ' . $key;
                continue;
            }

            $html .= ' ' . $key . '="' . e($value) . '"';
        }

        return $html;
    }

    protected static function toArray($list)
    {
        if (is_array($list)) {
            return $list;
        }

        if ($list instanceof \Traversable) {
            return iterator_to_array($list);
        }

        if (is_object($list) && method_exists($list, 'toArray')) {
            return $list->toArray();
        }

        return (array) $list;
    }
}
