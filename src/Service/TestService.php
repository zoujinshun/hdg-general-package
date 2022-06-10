<?php

namespace Vaedly\HdgGeneralPackage\Service;

use Vaedly\HdgGeneralPackage\Repository\TestRepository;

class TestService
{
    public function test()
    {
        return (new TestRepository())->test();
    }
}