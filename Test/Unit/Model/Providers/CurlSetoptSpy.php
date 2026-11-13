<?php

declare(strict_types=1);

namespace Mageplaza\SocialLogin\Model\Providers;

function curl_setopt($handle, $option, $value)
{
    $GLOBALS['mp_curl_setopt_calls'][$option] = $value;

    return \curl_setopt($handle, $option, $value);
}
