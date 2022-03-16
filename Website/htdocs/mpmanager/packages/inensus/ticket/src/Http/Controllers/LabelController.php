<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 14:49
 */

namespace Inensus\Ticket\Http\Controllers;


use Illuminate\Http\Request;
use Inensus\Ticket\Http\Requests\LabelRequest;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Models\Label;

class LabelController extends Controller
{

    private $label;

    public function __construct(Label $label)
    {
        $this->label = $label;
    }

    /**
     * A list of all stored labels/categories
     */
    public function index(Request $request)
    {

        $outsource = $request->get('outsource');

        if ($outsource) {
            $labels = Label::where('out_source', 1)->get();
        } else {
            $labels = Label::all();
        }
        return new TicketResource($labels);
    }


    public function store(LabelRequest $request)
    {
        $this->label->label_name = request('labelName');
        $this->label->label_color = request('labelColor');
        $this->label->out_source = request('outSourcing');
        $this->label->save();
        return new TicketResource($this->label);
    }
}
