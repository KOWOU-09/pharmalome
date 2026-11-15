<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        Mail::to(config('services.contact.email'))->send(new ContactMessage(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            topic: $validated['subject'],
            body: $validated['message'],
        ));

        return to_route('contact')->with('status', 'Votre message a bien été envoyé. Notre équipe vous répondra dès que possible.');
    }
}
