<?php

namespace Simplex\Core\Component\Stub;

use Simplex\Core\ComponentBase;

class DefaultStub extends ComponentBase
{

    protected function content()
    {
        echo 'This is DefaultStub component';
    }
}