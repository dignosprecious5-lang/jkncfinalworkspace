<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of clients with search and statistics.
     */
    public function index(Request $request)
    {
        $query = Client::withCount(['proposals', 'requirements']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        $clients = $query->latest()->get();

        return view('clients.index', compact('clients'));
    }

    /**
     * Store a new client record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:clients,email',
            'company'     => 'nullable|string|max:255',
            'client_type' => 'required|in:individual,corporation,opc,partnership',
            'phone'       => 'nullable|string|max:50',
        ]);

        Client::create($validated);

        return back()->with('success', 'Client account created successfully.');
    }

    /**
     * Display client profile, active contracts, and uploaded documents.
     */
    public function show($id)
    {
        $client = Client::with(['proposals.service', 'requirements'])->findOrFail($id);

        return view('clients.show', compact('client'));
    }

    /**
     * Remove client record.
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return back()->with('success', 'Client removed successfully.');
    }
}