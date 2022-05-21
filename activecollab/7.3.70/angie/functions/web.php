<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * All web related functions - content forwarding, redirections, header
 * manipulation etc.
 *
 * @package angie.functions
 */

/**
 * Forward specific file to the browser as a stream of data.
 *
 * Download can be forced (disposition: attachment) or passed inline
 *
 * @param  string $path                    File path
 * @param  string $type                    Serve file as this type
 * @param  string $name                    If set use this name, else use filename (basename($path))
 * @param  bool   $force_download          Force download (add Disposition => attachement)
 * @param  bool   $die
 * @param  bool   $delete_source_when_done
 * @return bool
 */
function download_file($path, $type = 'application/octet-stream', $name = null, $force_download = false, $die = true, $delete_source_when_done = false)
{
    if (!defined('HTTP_LIB_PATH')) {
        require ANGIE_PATH . '/classes/http/init.php';
    }

    // Prepare variables
    if (empty($name)) {
        $name = basename($path);
    }

    if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
        $name = urlencode($name); // Fix problem with non-ASCII characters in IE
    }

    $disposition = $force_download ? HTTP_DOWNLOAD_ATTACHMENT : HTTP_DOWNLOAD_INLINE;

    // Make sure that system is usable while download is running
    if ($die) {
        session_write_close();
    }

    // Prepare and send file
    $download = new HTTP_Download();
    $download->setFile($path, true);
    $download->setContentType($type);
    $download->setContentDisposition($disposition, $name);

    $download->send();

    if ($delete_source_when_done) {
        unlink($path);
    }

    if ($die) {
        exit();
    }
}

/**
 * Use content (from file, from database, other source...) and pass it to the
 * browser as a file.
 *
 * @param string $content
 * @param string $type           MIME type
 * @param string $name           File name
 * @param bool   $force_download Send Content-Disposition: attachment to force
 *                               save dialog
 * @param bool   $die
 */
function download_contents($content, $type, $name, $force_download = false, $die = true)
{
    if (!defined('HTTP_LIB_PATH')) {
        require ANGIE_PATH . '/classes/http/init.php';
    }

    if (isset($_SERVER) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
        $name = urlencode($name); // Fix problem with non-ASCII characters in IE
    }

    $disposition = $force_download ? HTTP_DOWNLOAD_ATTACHMENT : HTTP_DOWNLOAD_INLINE;

    // Make sure that system is usable while download is running
    if ($die) {
        session_write_close();
    }

    // Prepare and send file
    $download = new HTTP_Download();
    $download->setData($content);
    $download->setContentType($type);
    $download->setContentDisposition($disposition, $name);

    $download->send();

    if ($die) {
        exit();
    }
}

/**
 * Get response from remote server.
 *
 * @param               $url
 * @param  null         $request_headers
 * @return mixed|string
 */
function response_from_server($url, $request_headers = null)
{
    $proxy_settings = ConfigOptions::getValue([
        'network_proxy_enabled',
        'network_proxy_protocol',
        'network_proxy_address',
        'network_proxy_port',
    ]);

    if (function_exists('curl_init')) {
        // initialise curl
        $curl = curl_init($url);

        // set curl options
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        if ($request_headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers); // send request headers
        }

        if ($proxy_settings['network_proxy_enabled']) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_settings['network_proxy_address'] . ':' . $proxy_settings['network_proxy_port']);
        }

        // get response
        $response = curl_exec($curl);

        // close connection
        curl_close($curl);
        unset($curl);

        // return response
        return $response;
    } elseif (ini_get('allow_url_fopen')) {
        $stream_context_http_params = [
            'method' => 'GET',
        ];

        // if there are request headers
        if ($request_headers) {
            $stream_context_http_params['header'] = implode("\r\n", $request_headers) . "\r\n";
        }

        // if proxy is enabled
        if ($proxy_settings['network_proxy_enabled']) {
            $prefix = strtolower($proxy_settings['network_proxy_protocol']) == 'http' ? 'tcp' : 'ssl';
            $stream_context_http_params['proxy'] = $prefix . '://' . $proxy_settings['network_proxy_address'] . ':' . $proxy_settings['network_proxy_port'];
            $stream_context_http_params['request_fulluri'] = true;
        }

        // default stream context create options
        $stream_context_create_options = [
            'http' => $stream_context_http_params,
        ];

        // create stream context create
        $stream_context_create = stream_context_create($stream_context_create_options);

        // perform the request and get contents
        return file_get_contents($url, false, $stream_context_create);
    }
}

/**
 * Check and set a valid protocol for a given URL.
 *
 * This function will check if $url has a protocol part and if it does not have
 * it will add it. If $ignore_empty is set to true and $url is an emapty string
 * empty string will be returned back (good for optional URL fields).
 *
 * @param  string $url
 * @param  bool   $ignore_empty
 * @param  string $protocol
 * @return string
 */
function valid_url_protocol($url, $ignore_empty = false, $protocol = 'http')
{
    $trimmed = trim($url);
    if (($trimmed == '') && $ignore_empty) {
        return '';
    }

    if (strpos($trimmed, '://') === false) {
        return "$protocol://$trimmed";
    } else {
        return $trimmed;
    }
}

/**
 * Replace spaces in URLs with %20.
 *
 * @param  string $url
 * @return string
 */
function replace_url_spaces($url)
{
    return str_replace(' ', '%20', $url);
}

// ---------------------------------------------------
//  HTML generators
// ---------------------------------------------------

/**
 * Open HTML tag.
 *
 * @param  string $name       Tag name
 * @param  array  $attributes Array of tag attributes
 * @param  bool   $empty      If tag is empty it will be automaticly closed
 * @return string
 */
function open_html_tag($name, $attributes = null, $empty = false)
{
    $attribute_string = '';
    if (is_array($attributes) && count($attributes)) {
        $prepared_attributes = [];
        foreach ($attributes as $k => $v) {
            if (trim($k) != '') {
                if (is_bool($v)) {
                    if ($v) {
                        $prepared_attributes[] = "$k=\"$k\"";
                    }
                } else {
                    $prepared_attributes[] = $k . '="' . clean($v) . '"';
                }
            }
        }
        $attribute_string = implode(' ', $prepared_attributes);
    }

    $empty_string = $empty ? ' /' : ''; // Close?

    return "<$name $attribute_string$empty_string>"; // And done...
}

/**
 * Render form label element. This helper makes it really simple to mark reqired elements
 * in a standard way.
 *
 * @param  string $text        Label content
 * @param  string $for         ID of related elementet
 * @param  bool   $is_required Mark as a required fiedl
 * @param  array  $attributes  Additional attributes
 * @param  string $after_label Label text sufix
 * @return string
 */
function label_tag($text, $for = null, $is_required = false, $attributes = null, $after_label = ':')
{
    if (trim($for)) {
        if (is_array($attributes)) {
            $attributes['for'] = trim($for);
        } else {
            $attributes = ['for' => trim($for)];
        }
    }

    $render_text = trim($text) . $after_label;
    if ($is_required) {
        $render_text .= ' <span class="label_required">*</span>';
    }

    return open_html_tag('label', $attributes) . $render_text . '</label>';
}

/**
 * Render radio field.
 *
 * @param  string $name       Field name
 * @param  bool   $checked
 * @param  array  $attributes Additional attributes
 * @return string
 */
function radio_field($name, $checked = false, $attributes = null)
{
    if (is_array($attributes)) {
        $attributes['type'] = 'radio';
        if (!isset($attributes['class'])) {
            $attributes['class'] = 'inline';
        }
    } else {
        $attributes = ['type' => 'radio', 'class' => 'inline'];
    }

    // Value
    $value = array_var($attributes, 'value', false);
    if ($value === false) {
        $value = 'checked';
    }

    // Checked
    if ($checked) {
        $attributes['checked'] = 'checked';
    } else {
        if (isset($attributes['checked'])) {
            unset($attributes['checked']);
        }
    }

    $attributes['name'] = $name;
    $attributes['value'] = $value;

    return open_html_tag('input', $attributes, true);
}

/**
 * Render select list box.
 *
 * Options is array of already rendered option and optgroup tags
 *
 * @param  array  $options    Array of already rendered option and optgroup tags
 * @param  array  $attributes Additional attributes
 * @return string
 */
function select_box($options, $attributes = null)
{
    $output = open_html_tag('select', $attributes) . "\n";
    if (is_array($options)) {
        foreach ($options as $option) {
            $output .= $option . "\n";
        }
    }

    $output .= '</select>' . "\n";

    return $output;
}

/**
 * Render option tag.
 *
 * @param  string $text       Option text
 * @param  mixed  $value      Option value
 * @param  array  $attributes
 * @return string
 */
function option_tag($text, $value = null, $attributes = null)
{
    if (!is_null($value)) {
        if (is_array($attributes)) {
            $attributes['value'] = $value;
        } else {
            $attributes = ['value' => $value];
        }
    }

    return open_html_tag('option', $attributes) . clean($text) . '</option>';
}

/**
 * Render option group.
 *
 * @param  string $label      Group label
 * @param  array  $options
 * @param  array  $attributes
 * @return string
 */
function option_group_tag($label, $options, $attributes = null)
{
    if (is_array($attributes)) {
        $attributes['label'] = $label;
    } else {
        $attributes = ['label' => $label];
    }

    $output = open_html_tag('optgroup', $attributes) . "\n";
    if (is_array($options)) {
        foreach ($options as $option) {
            $output .= $option . "\n";
        }
    }

    return $output . '</optgroup>' . "\n";
}

/**
 * Converts url to path.
 *
 * @param  string       $url
 * @return mixed|string
 */
function url_to_path($url)
{
    if (strpos($url, 'path_info=') !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $url_params);
        $path = isset($url_params['path_info']) ? $url_params['path_info'] : null;
    } elseif (strpos($url, ROOT_URL) !== false) {
        $path = str_replace(ROOT_URL, '', $url);
    } else {
        $path = parse_url($url)['path'];
    }

    if (substr($path, 0, 1) != '/') {
        $path = '/' . $path;
    }

    return $path;
}
