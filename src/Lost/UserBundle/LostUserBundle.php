<?php

namespace Lost\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LostUserBundle extends Bundle
{
    public function getParent() {
        return 'FOSUserBundle';
    }

}
