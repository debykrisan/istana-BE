<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class KontakController extends Controller
{
    public function kontak(Request $request)
    {
        $hasil = $this->createMail($request);
        if ($hasil['status'] == 200) {
            return response()->json([
                'status' => 200,
                'message' => $hasil['message']
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
        
    }
    private function createMail(Request $request)  
    {
        try {
            $data = array(
                "data" => [
                    'nama' => $request->nama,
                    'email' => $request->email,
                    'pesan' => $request->pesan
                ]
            );
    
            $html = view('kontakmail', $data)->render();
    
            $client = new Client();
    
            $response = $client->post(env('SPRING_BOOT_SMTP_ENDPOINT'), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'sender' => $request->email,
                    'recipient' => 'info@istanapresiden.go.id',
                    'msgBody' => $html,
                    'subject' => 'Kontak dari ' . $request->nama,
                ],
            ]);
    
            $token = array(
                "status" => $response->getStatusCode(),
                "message" => "Terima Kasih, pesan anda berhasil dikirim"
            );
    
            return $token;
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500); 
        }
    }
}
