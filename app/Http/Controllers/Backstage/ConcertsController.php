<?php

namespace App\Http\Controllers\Backstage;

use App\Events\ConcertAdded;
use App\Http\Controllers\Controller;
use App\NullFile;
use Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class ConcertsController extends Controller
{
    public function index()
    {
        /*        return view('backstage.concerts.index', ['concerts' => Auth::user()->concerts]);*/
        return view('backstage.concerts.index', [
            'publishedConcerts' => Auth::user()->concerts->filter->isPublished(),
            'unpublishedConcerts' => Auth::user()->concerts->reject->isPublished()
        ]);
    }

    public function create()
    {
        return view('backstage.concerts.create');

    }


    public function store()
    {


        $this->validate(request(), [
            'title' => 'required',
            'date' => 'required|date',
            'time' => 'required|date_format:g:ia',
            'venue' => 'required',
            'venue_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'ticket_price' => 'required|numeric|min:5',
            'ticket_quantity' => 'required|numeric|min:1',
            'poster_image' => ['nullable', 'image', Rule::dimensions()->minWidth(400)->ratio(8.5 / 11)]

            /*            'poster_image' => ['nullable', 'image', Rule::dimensions()->minWidth(400)->ratio(8.5 / 11)]*/
        ]);
        $concert = Auth::user()->concerts()->create([
            'title' => request('title'),
            'subtitle' => request('subtitle'),
            'date' => Carbon::parse(vsprintf('%s %s', [request('date'), request('time')])),
            'ticket_price' => request('ticket_price') * 100,
            'venue' => request('venue'),
            'venue_address' => request('venue_address'),
            'city' => request('city'),
            'zip' => request('zip'),
            'state' => request('state'),
            'ticket_quantity' => (int)request('ticket_quantity'),
            'additional_information' => request('additional_information'),
            'poster_image_path' => request('poster_image', new NullFile)->store('posters', 'public'),
        ]);

        //  $concert->publish();


        ConcertAdded::dispatch($concert);

        return redirect()->route('backstage.concerts.index');

    }

    public function edit($id)
    {
        $concert = Auth::user()->concerts()->findOrFail($id);

        /*        $concert = Concert::fromCurrentUser()->findOrFail($id);*/

        abort_if($concert->isPublished(), 403);

        return view('backstage.concerts.edit', ['concert' => $concert]);
    }


    public function update($id)
    {
        $concert = Auth::user()->concerts()->findOrFail($id);

        $this->validate(request(), [
            'title' => 'required',
            'date' => 'required|date',
            'time' => 'required|date_format:g:ia',
            'venue' => 'required',
            'venue_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'ticket_price' => 'required|numeric|min:5',
            'ticket_quantity' => 'required|integer|min:1',
            /*            'poster_image' => ['nullable', 'image', Rule::dimensions()->minWidth(400)->ratio(8.5 / 11)]*/
        ]);


        abort_if($concert->isPublished(), 403);

        $concert->update([
            'title' => request('title'),
            'subtitle' => request('subtitle'),
            'date' => Carbon::parse(vsprintf('%s %s', [request('date'), request('time')])),
            'venue' => request('venue'),
            'venue_address' => request('venue_address'),
            'city' => request('city'),
            'state' => request('state'),
            'zip' => request('zip'),
            'ticket_price' => request('ticket_price') * 100,
            'ticket_quantity' => (int)request('ticket_quantity'),
            'additional_information' => request('additional_information'),

        ]);

        return redirect()->route('backstage.concerts.index');

    }
}
