<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Gate;
use App\Ticket;
use App\Services\LicenseService;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Notifications\CommentEmailNotification;
use App\Services\ProcessTicketService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Reference\Url;
use Symfony\Component\HttpFoundation\Response;


class TicketController extends Controller
{
    use MediaUploadingTrait;

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'repo'          => 'required',
            'author_email'  => 'required|email',
            'fileTypes'     => 'required|min:1'
        ]);

        $request->request->add([
            'category_id'   => 1,
            'status_id'     => 1,
            'priority_id'   => 1,
            'extensions'    => implode(',', $request->fileTypes)
        ]);

        $ticket = Ticket::create($request->all());

        foreach ($request->input('attachments', []) as $file) {
            $ticket->addMedia(storage_path('tmp/uploads/' . $file))->toMediaCollection('attachments');
        }

        if($request->submit == "run")
        {
            $process = new ProcessTicketService($ticket->id);

            if($process->run())
            {
                $ticket->status_id = 2;
                $ticket->save();
            }
        }

        return redirect()->back()->withStatus('Your VCS licensing ticket has been issued. You can view ticket status <a href="'.route('tickets.show', $ticket->id).'">here</a>');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function show(Ticket $ticket)
    {
        $ticket->load('comments');

        return view('tickets.show', compact('ticket'));
    }

    public function storeComment(Request $request, Ticket $ticket)
    {
        abort_if(Gate::denies('comment_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'comment_text' => 'required'
        ]);

        $user = Auth::user();

        $comment = $ticket->comments()->create([
            'author_name'   => $user->name,
            'author_email'  => $user->email,
            'comment_text'  => $request->comment_text
        ]);

        $ticket->sendCommentNotification($comment);

        return redirect()->back()->withStatus('Your comment was added successfully');
    }
}
