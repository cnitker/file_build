<?php
namespace cnitker;

use cnitker\command\fileBuild;

class Service extends \think\Service
{
    public function boot(): void
    {
        $this->commands([
            fileBuild::class
        ]);
    }


}
