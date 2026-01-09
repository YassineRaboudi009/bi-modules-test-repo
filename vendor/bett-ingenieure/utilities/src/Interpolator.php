<?php

/**
 *
 * Copyright (C) 2025, Bett Ingenieure GmbH - All Rights Reserved
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Bett Ingenieure GmbH BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace BettIngenieure\Utilities;

use BettIngenieure\Base\BaseClass;

class Interpolator extends BaseClass {

    /**
     * Replace placeholders in a raw string with their values
     * Optional: Escape the values by a callback
     *
     * Placeholders: ? for numeric value keys or :string for string value keys
     *
     * If a placeholder could exist in the raw string and should not be replaced,
     * the string should be escaped by using `escapePlaceholder`
     *
     * @param string $raw                           Raw can contain placeholders: ? for numeric value keys or :string for string value keys
     * @param null|\Closure $valueEscapeCallback    fn(string $s) : string
     */
    public function do(string $raw, array $values, \Closure $valueEscapeCallback = null) : string {

        $placeHolderRegex = [];
        $placeHolderEscaped = [];
        $placeHolderUnescaped = [];

        $doesNotStartWithSlashExpression = '(?<!\\\\)';

        // build a regular expression for each parameter
        foreach( $values as $key => $value ) {
            if( is_string($key) ) {
                $placeHolderRegex[] = '/' . $doesNotStartWithSlashExpression . ':' . preg_quote($key, '/') . '/';
                $placeHolderEscaped[] = '\\:' . $key;
                $placeHolderUnescaped[] = ':' . $key;
            } else {
                $placeHolderRegex[] = '/' . $doesNotStartWithSlashExpression . '\?/';
                $placeHolderEscaped[] = '\\?';
                $placeHolderUnescaped[] = '?';
            }
        }

        if($valueEscapeCallback !== null) {
            $values = array_map($valueEscapeCallback, $values);
        }

        foreach($values as $key => $value) {
            $values[$key] = preg_replace($placeHolderRegex, $placeHolderEscaped, $value, 1);
        }
        $values = array_map(fn($value) => str_replace(['\\', '$',], ['\\\\', '\$',], $value), $values);

        $result = preg_replace($placeHolderRegex, $values, $raw, 1, $count);

        if($count !== count($values)) {
            throw new \RuntimeException('Interpolate count ' . $count . ' != values count of ' . count($values));
        }

        if(count($placeHolderEscaped) == 0) { // Replace \? everytime, because the raw value could be escaped
            $placeHolderEscaped[] = '\\?';
            $placeHolderUnescaped[] = '?';
        }

        return str_replace($placeHolderEscaped, $placeHolderUnescaped, $result);
    }

    public function escapePlaceholder(string $raw, array $values = []) : string {

        $result = $raw;

        $placeholders = [];

        foreach( $values as $key => $value ) {
            if( is_string($key) ) {
                $placeholders[':' . $key] = 1;
            }
        }
        $placeholders['?'] = 1;

        foreach(array_keys($placeholders) as $placeholder) {
            $result = str_replace($placeholder, '\\' . $placeholder, $result);
        }

        return $result;
    }
}