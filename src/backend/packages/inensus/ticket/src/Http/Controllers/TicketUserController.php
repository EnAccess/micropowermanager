<?php

namespace Inensus\Ticket\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Inensus\Ticket\Http\Requests\TicketingUserExternalRequest;
use Inensus\Ticket\Http\Requests\TicketingUserRequest;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketUserService;

class TicketUserController extends Controller {
    public function __construct(
        private TicketUserService $ticketUserService,
        private UserService $userService,
    ) {}

    public function index(Request $request): TicketResource {
        $limit = $request->input('per_page');
        $outSource = $request->input('outsource');

        return TicketResource::make($this->ticketUserService->getAll($limit, $outSource));
    }

    public function storeExternal(TicketingUserExternalRequest $request) {
        $ticketUserData = [
            'user_name' => $request->getUserName(),
            'phone' => $request->getPhone(),
            'out_source' => true,
            'user_id' => null,
        ];

        $this->ticketUserService->create($ticketUserData);
    }

    public function store(TicketingUserRequest $request): TicketResource {
        $user = $this->userService->get($request->getUserId());

        $ticketUserData = [
            'user_name' => $user->getName(),
            'phone' => null,
            'out_source' => 0,
            'user_id' => $user->getId(),
        ];
        $ticketUser = $this->ticketUserService->create($ticketUserData);

        return TicketResource::make($ticketUser);
    }
}
