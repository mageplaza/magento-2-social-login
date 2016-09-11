<?php
/**
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
namespace Mageplaza\SocialLogin\Model\Facebook;

if (!function_exists('curl_init')) {
    throw new \Exception('Facebook needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
    throw new \Exception('Facebook needs the JSON PHP extension.');
}

/**
 * Thrown when an API call returns an exception.
 *
 * @author Naitik Shah <naitik@facebook.com>
 */
class ApiException extends \Exception
{
    /**
     * The result from the API server that represents the exception information.
     */
    protected $result;

    /**
     * Make a new API Exception with the given result.
     *
     * @param Array $result the result from the API server
     */
    public function __construct($result)
    {
        $this->result = $result;

        $code = isset($result['error_code']) ? $result['error_code'] : 0;

        if (isset($result['error_description'])) {
            // OAuth 2.0 Draft 10 style
            $msg = $result['error_description'];
        } else if (isset($result['error']) && is_array($result['error'])) {
            // OAuth 2.0 Draft 00 style
            $msg = $result['error']['message'];
        } else if (isset($result['error_msg'])) {
            // Rest server style
            $msg = $result['error_msg'];
        } else {
            $msg = 'Unknown Error. Check getResult()';
        }

        parent::__construct($msg, $code);
    }

    /**
     * Return the associated result object returned by the API server.
     *
     * @returns Array the result from the API server
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns the associated type for the error. This will default to
     * 'Exception' when a type is not available.
     *
     * @return String
     */
    public function getType()
    {
        if (isset($this->result['error'])) {
            $error = $this->result['error'];
            if (is_string($error)) {
                // OAuth 2.0 Draft 10 style
                return $error;
            } else if (is_array($error)) {
                // OAuth 2.0 Draft 00 style
                if (isset($error['type'])) {
                    return $error['type'];
                }
            }
        }

        return 'Exception';
    }

    /**
     * To make debugging easier.
     *
     * @returns String the string representation of the error
     */
    public function __toString()
    {
        $str = $this->getType() . ': ';
        if ($this->code != 0) {
            $str .= $this->code . ': ';
        }

        return $str . $this->message;
    }
}

