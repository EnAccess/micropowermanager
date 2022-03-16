<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 14:50
 */

namespace Inensus\Ticket\Models;


/**
 * Class Label
 *
 * @package Inensus\Ticket\Models
 * @property integer $id
 * @property string $label_name
 * @property string $label_color
 * @property boolean $out_source
 */
class
Label extends BaseModel
{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('tickets.table_names')['ticket_categories']);
    }
}
