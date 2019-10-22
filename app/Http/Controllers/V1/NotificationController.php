<?php

namespace App\Http\Controllers\V1;


use App\Http\Controllers\Controller;
use App\Jobs\ProcessBroadcastMessage;
use App\Libraries\InternalWampClient\Client;
use App\Libraries\InternalWampClient\ClientNotificationMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class NotificationController extends Controller
{

    /**
     * Sends a notification to multiple users
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|max:255',
            'message' => 'required',
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'string|distinct|min:1|max:50'
        ]);

        $type =  $request->get('type');

        $seesionIds= $request->input('ids');
        $seesionIds = is_array($seesionIds) ? $seesionIds : [$seesionIds];

        $message = new ClientNotificationMessage($type, $request->get('message'));


        $job = $this->dispatch(new ProcessBroadcastMessage($message, $seesionIds));

        return new Response(['job' => $job]);

    }

    public function createBroadcast(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|max:255',
            'domain' => 'nullable|array|min:1|max:50',
            'message' => 'required',
        ]);

        $type =  $request->get('type');
        $domains = $request->get('domain'); // notify sessions in specific group/domain
        $domains = empty($domains) ? null : $domains;

        $message = new ClientNotificationMessage($type, $request->get('message'));

        $job = $this->dispatch(new ProcessBroadcastMessage($message, $domains));

        return new Response(['job' => $job]);
    }


    public function stats() {

        return new Response( (array)app(Client::class)->getStats());
    }


}
