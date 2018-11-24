<?php

// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace ILAB\MediaCloud\Tools\Glide\Server\Manipulators;

class Padding extends Border {

    public function __get($name) {
        if ($name == 'border') {
            $name = 'padding';
        }

        return parent::__get($name);
    }
}