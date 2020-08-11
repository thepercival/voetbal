<?php

namespace Voetbal;

interface Identifiable
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @param int|string $id
     * @return mixed
     */
    public function setId($id);
}
