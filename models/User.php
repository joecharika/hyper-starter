<?php

/**
 * Hyper v1.0.2-beta.2 (https://hyper.starlight.co.zw)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Models;


use Hyper\SQL\SQLAttributes;

/**
 * Class User
 * Special attention was given to this model
 * :: The authentication model must always extend the \Hyper\Models\User
 * :: This class is useful for adding custom fields to your user object
 * @package Models
 */
class User extends \Hyper\Models\User
{
    /**
     * @SQLType varchar(200)
     * :: This annotation is used for specifying the SQL Type of the filed, 'TEXT' is used by default
     *
     * @SQLAttributes unique
     * :: This annotation is used for defining SQL Attributes
     *
     * @var string $additionalField
     */
    public $additionalField;
}
