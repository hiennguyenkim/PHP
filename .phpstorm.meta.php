<?php

namespace PHPSTORM_META {
    override(
        [\Psr\Container\ContainerInterface::class, 'get'],
        map(['@'])
    );
}
