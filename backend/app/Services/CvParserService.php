<?php

namespace App\Services;

use App\Models\CandidateDocument;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CvParserService
{
    /**
     * Send a stored CV to the parser microservice and return the extracted JSON.
     *
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function parse(CandidateDocument $cv): array
    {
        $path = Storage::disk('local')->path($cv->file_path);
        $response = Http::timeout(60)
            ->attach('file', file_get_contents($path), $cv->file_name)
            ->post(config('services.cv_parser.url').'/parse');

        $response->throw();

        return $response->json();
    }
}