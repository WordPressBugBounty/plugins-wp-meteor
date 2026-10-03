<?php

/**
 * WP_Meteor
 *
 * @package   WP_Meteor
 * @author    Aleksandr Guidrevitch <alex@excitingstartup.com>
 * @copyright 2020 wp-meteor.com
 * @license   GPL 2.0+
 * @link      https://wp-meteor.com
 */

namespace WP_Meteor\Blocker\FirstInteraction;

use WP_Meteor\Blocker\Event;
/**
 * Provide Import and Export of the settings of the plugin
 */
class UltimateReorder extends Base
{
    public $adminPriority = -1;
    public $priority = 99;
    public $tab = 'ultimate';
    public $title = 'Maximum available speed';
    public $description = ""; //"Delays script loading to 2 seconds";
    public $disabledInUltimateMode = false;
    public $defaultEnabled = false;

    public $pattern = [['.*', '']];

    public function initialize()
    {
        parent::initialize();
        \add_filter(WPMETEOR_TEXTDOMAIN . '-frontend-rewrite', [$this, 'frontend_rewrite'], $this->priority, 2);
    }

    public function backend_display_settings()
    {
        echo '<div id="' . $this->id . '" class="ultimate"
                    data-prefix="' . $this->id . '" 
                    data-title="' . $this->title . '"></div>';
    }

    public function backend_save_settings($sanitized, $settings)
    {
        // $exists = isset($sanitized[$this->id]['enabled']);
        $merged = array_merge($settings[$this->id], $sanitized[$this->id] ?: []);
        $merged['enabled'] = true;
        $sanitized[$this->id] = $merged;
        return $sanitized;
    }

    /* triggered from wpmeteor_load_settings */
    public function load_settings($settings)
    {
        $settings[$this->id] = isset($settings[$this->id])
            ? $settings[$this->id]
            : ['enabled' => true];

        $settings[$this->id]['id'] = $this->id;
        $settings[$this->id]['delay'] = isset($settings[$this->id]['delay']) ? (int) $settings[$this->id]['delay'] : 0;
        // $settings[$this->id]['after'] = 'REORDER';
        $settings[$this->id]['description'] = $this->description;
        // var_dump($settings); exit;
        return $settings;
    }

    public function frontend_rewrite($buffer, $settings)
    {
        // Fast Velocity Minify Delay JS compatibility
        /*
        if (is_plugin_active('fast-velocity-minify/fvm.php')) {
            $buffer = preg_replace('/\s+type=([\'"])fvm-script-delay\1/i', ' type=\'text/javascript\'', $buffer);
        }
        */

        /*
        if (is_plugin_active('wp-rocket/wp-rocket.php')) {
            $buffer = preg_replace('/\s+type=([\'"])rocketlazyloadscript\1\s+data-rocket-type=([\'"])text\/javascript\2/i', ' type=\'text/javascript\'', $buffer);
            // type="rocketlazyloadscript" data-rocket-type='text/javascript'
        }*/

        $EXTRA = defined('WPMETEOR_EXTRA_ATTRS') ? constant('WPMETEOR_EXTRA_ATTRS') : '';
        $DELIMITER = "WPMETEOR" . wp_generate_password(16, false);

        $REPLACEMENTS = [];
        $searchOffset = 0;
        while (preg_match('/<script\b[^>]*?>/is', $buffer, $matches, PREG_OFFSET_CAPTURE, $searchOffset)) {
            $offset = $matches[0][1];
            $searchOffset = $offset + 1;
            if (preg_match('/<\/\s*script>/is', $buffer, $endMatches, PREG_OFFSET_CAPTURE, $matches[0][1])) {
                $len = $endMatches[0][1] - $matches[0][1] + strlen($endMatches[0][0]);
                // $everything = substr($buffer, $matches[0][1], $len);
                $tag = $matches[0][0];
                $closingTag = $endMatches[0][0];

                $hasSrc = preg_match('/\s+src=/i', $tag);
                $hasType = preg_match('/\s+type=/i', $tag);
                $shouldReplace = !$hasType || preg_match('/\s+type=([\'"])((application|text)\/(javascript|ecmascript|html|template)|module)\1/i', $tag);
                $noOptimize = preg_match('/data-wpmeteor-nooptimize="true"/i', $tag);
                if ($shouldReplace && !$hasSrc) {
                    // inline script
                    $content = substr($buffer, $matches[0][1] + strlen($matches[0][0]), $endMatches[0][1] - $matches[0][1] - strlen($matches[0][0]));
                    if (!$noOptimize && apply_filters('wpmeteor_exclude', false, $content)) {
                        $tag = preg_replace('/^<script\b/i', "<script {$EXTRA} data-wpmeteor-nooptimize=\"true\"", $tag);
                    }
                    $replacement = $tag . $DELIMITER . "[" . count($REPLACEMENTS) . "]" . $DELIMITER . $closingTag;
                    $REPLACEMENTS[] = $content;
                    $buffer = substr_replace($buffer, $replacement, $offset, $len);
                    continue;
                }
            }
        }

        $buffer = preg_replace_callback('/<script\b[^>]*?>/is', function ($matches) use ($EXTRA) {
            list($tag) = $matches;

            $result = $tag;
            if (!preg_match('/\s+data-src=/i', $result)
                && !preg_match('/\s+data-wpmeteor-type=/i', $result) 
                && !preg_match('/\s+data-pmdelayedscript=/i', $result) 
                && !preg_match('/data-wpmeteor-nooptimize="true"/i', $result)
                && !preg_match('/data-rocketlazyloadscript=/i', $result)) {

                $src = preg_match('/\s+src=([\'"])(.*?)\1/i', $result, $matches)
                    ? $matches[2]
                    : null;
                $id = preg_match('/\s+id=([\'"])(.*?)\1/i', $result, $matches)
                    ? $matches[2]
                    : null;
                if (!$src) {
                    // trying to detect src without quotes
                    $src = preg_match('/\s+src=([\/\w\-\.\~\:\[\]\@\!\$\?\&\#\(\)\*\+\,\;\=\%]+)/i', $result, $matches)
                        ? $matches[1]
                        : null;
                }
                $hasType = preg_match('/\s+type=/i', $result);
                $isJavascript = !$hasType
                    || preg_match('/\s+type=([\'"])((application|text)\/(javascript|ecmascript)|module)\1/i', $result)
                    || preg_match('/\s+type=((application|text)\/(javascript|ecmascript)|module)/i', $result);
                if ($isJavascript) {
                    if ($id && apply_filters('wpmeteor_exclude', false, $id)) {
                        return preg_replace('/<script/i', "<script {$EXTRA} ", $result);
                    }
                    if ($src) {
                        if (apply_filters('wpmeteor_exclude', false, $src)) {
                            return preg_replace('/<script/i', "<script {$EXTRA} ", $result);
                        }
                        $result = preg_replace('/\s+src=/i', " data-wpmeteor-src=", $result);
                        // $result = preg_replace('/\s+(async|defer|integrity)\b/i', " data-wpmeteor-\$1", $result);
                    }
                    if ($hasType) {
                        $result = preg_replace('/\s+type=([\'"])module\1/i', " type=\"javascript/blocked\" data-wpmeteor-type=\"module\" ", $result);
                        $result = preg_replace('/\s+type=module\b/i', " type=\"javascript/blocked\" data-wpmeteor-type=\"module\" ", $result);
                        $result = preg_replace('/\s+type=([\'"])(application|text)\/(javascript|ecmascript)\1/i', " type=\"javascript/blocked\" data-wpmeteor-type=\"$2/$3\" ", $result);
                        $result = preg_replace('/\s+type=(application|text)\/(javascript|ecmascript)\b/i', " type=\"javascript/blocked\" data-wpmeteor-type=\"$1/$2\" ", $result);
                    } else {
                        $result = preg_replace('/<script/i', "<script type=\"javascript/blocked\" data-wpmeteor-type=\"text/javascript\" ", $result);
                    }
                    // $result = preg_replace('/<script/i', "<script {$EXTRA} data-wpmeteor-after=\"REORDER\"", $result);
                    $result = preg_replace('/<script/i', "<script {$EXTRA}", $result);
                }
            }
            return $result; // preg_replace('/\s*data-wpmeteor-nooptimize="true"/i', '', $result);
        }, $buffer);

        // we don't rewrite 
        /* $buffer = preg_replace_callback('/<(body|img|iframe|script)\b[^>]*?>/is', function ($matches) { */
        // the buffer is tokenized with the HTML5 tokenizer rules, so a handler is rewritten only where
        // browsers see a real onload / onerror attribute, never inside an attribute value, see CVE-2026-96572:
        // - comments, raw text elements (their content is not markup) and end tags are matched as a whole and kept,
        //   an unclosed comment, raw text element, tag or quoted value runs to the end of the buffer, like in browsers
        // - a tag name runs up to whitespace, "/" or ">" (<img'x is not an img)
        // - an attribute name runs up to whitespace, "/", ">" or "=", a value is quoted only if a quote follows "="
        //   (alt=Don't is an unquoted value), a quoted value may contain ">", "<img" or " onerror="
        // covered by test/test-xss-delimiter.php
        $ws = '[\t\n\f\r ]';
        $attr = '[^\t\n\f\r \/>][^\t\n\f\r \/>=]*+(?:' . $ws . '*+=' . $ws . '*+(?:"[^"]*+(?:"|\z)|\'[^\']*+(?:\'|\z)|[^\t\n\f\r >]*+))?';
        $attrs = '(?:[\t\n\f\r \/]++|' . $attr . ')*+';
        $end = '(?=[\t\n\f\r \/>])';
        // iframe and noscript (with scripting enabled) content is raw text too
        $rawText = 'script|style|textarea|title|xmp|noembed|noframes|noscript|iframe';
        // content loops are possessive, a lazy .*? hits pcre.backtrack_limit on large style / noscript blocks
        $rewritten = preg_replace_callback(
            "/<!--(?:>|->|[^-]*+(?:-(?!-!?>)[^-]*+)*+(?:--!?>|\z))|<[!?][^>]*+(?:>|\z)|<\/[a-z][^\\t\\n\\f\\r \/>]*+{$attrs}(?:>|\z)|<\/[^>]*+(?:>|\z)"
            . "|<(?<raw>{$rawText})(?<rawattrs>{$end}{$attrs})(?:>|\z)(?<content>[^<]*+(?:<(?!\/\k<raw>{$end})[^<]*+)*+(?:<\/\k<raw>{$end}{$attrs}>|\z))"
            . "|<(?<tag>[a-z][^\\t\\n\\f\\r \/>]*+)(?<attrs>{$attrs})(?:>|\z)/is",
            function ($matches) use ($attr) {
                $result = $matches[0];
                // the start tag of a raw text element is rewritten, its content is kept as is
                $name = !empty($matches['raw']) ? $matches['raw'] : (isset($matches['tag']) ? $matches['tag'] : '');
                $attrs = !empty($matches['raw']) ? $matches['rawattrs'] : (isset($matches['attrs']) ? $matches['attrs'] : '');
                $content = !empty($matches['raw']) ? $matches['content'] : '';

                // a tag that is not closed before the end of the buffer is dropped by browsers
                if (!in_array(strtolower($name), ['html', 'body', 'img', 'iframe'], true) || substr($result, strlen($name) + strlen($attrs) + 1, 1) !== '>') {
                    return $result;
                }

                // rewrite is called twice, so we don't want to rewrite twice
                if (preg_match('/data-wpmeteor-on(load|error)\b/i', $attrs)) {
                    return $result;
                }

                // attributes are matched one by one, so a value is never scanned for handlers
                $attrs = preg_replace_callback("/{$attr}/", function ($m) {
                    if (!preg_match('/^on(load|error)(?=[\t\n\f\r ]*=)/i', $m[0], $h)) {
                        return $m[0];
                    }
                    $handler = 'on' . strtolower($h[1]);
                    return sprintf('%s="window.dispatchEvent(new CustomEvent(\'%s\', { detail: { event: event, target: this } }))" data-wpmeteor-%s', $handler, Event::EVENT_ELEMENT_LOADED, $handler)
                        . substr($m[0], strlen($h[0]));
                }, $attrs);

                return '<' . $name . $attrs . '>' . $content;
            },
            $buffer
        );
        // on a regex failure the handlers stay as is, rather than an empty page
        if ($rewritten !== null) {
            $buffer = $rewritten;
        }

        /**
         * this should go the last, because there can be images inserted by scripts as with https://wbuac.progresssite.pro/ 
         * effectively breaking JSON
         * covered by test/test.php
         */
        $buffer = preg_replace_callback('/' . preg_quote($DELIMITER, '/') . '\[(\d+)\]' . preg_quote($DELIMITER, '/') . '/', function ($matches) use (&$REPLACEMENTS) {
            return $REPLACEMENTS[(int)$matches[1]];
        }, $buffer);

        return $buffer;
    }

    public function backend_adjust_wpmeteor($wpmeteor, $settings)
    {
        $wpmeteor['blockers'] = isset($wpmeteor['blockers']) ? $wpmeteor['blockers'] : [];
        $wpmeteor['blockers'][$this->id] = $settings[$this->id];

        if (isset($settings['v']) && version_compare($settings['v'], '2.3.6', '<') && 3 === (int) $settings[$this->id]['delay']) {
            $wpmeteor['blockers'][$this->id]['delay'] = -1;
        }
        return $wpmeteor;
    }

    public function frontend_adjust_wpmeteor($wpmeteor, $settings)
    {
        if (!$settings[$this->id]['enabled']) {
            $wpmeteor['rdelay'] = 0;
        } else {
            if (isset($settings['v']) && version_compare($settings['v'], '2.3.6', '<')) {
                $wpmeteor['rdelay'] = (int) $settings[$this->id]['delay'] === 3
                    ? 86400000 # one day
                    : (int) $settings[$this->id]['delay'] * 1000;
            } else {
                $wpmeteor['rdelay'] = (int) $settings[$this->id]['delay'] < 0
                    ? 86400000 # one day
                    : (int) $settings[$this->id]['delay'] * 1000;
            }
        }
        // var_dump($settings); exit;
        $wpmeteor['preload'] = true;
        $wpmeteor['preconnect'] = isset($wpmeteor['preconnect'])
            ? $wpmeteor['preconnect']
            : $wpmeteor['rdelay'] !== 86400000;
        return $wpmeteor;
    }
}
