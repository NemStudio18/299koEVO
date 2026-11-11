<?php

namespace Blog\Entities;

use Content\Categories\Category as CoreCategory;

defined('ROOT') OR exit('Access denied!');

class Category extends CoreCategory
{

    protected string $pluginId = 'blog';
    protected string $name = 'categories';
    protected bool $nested = true;
    protected bool $chooseMany = true;

}
