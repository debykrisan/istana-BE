<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $kanal = $request->kanal;
            $finalQuery = "";
            $orderByQuery = "ORDER BY tanggal_publikasi DESC";
            $paginationQuery = "LIMIT :limit OFFSET :offset";

            // Fetch data based on the value of $kanal
            switch ($kanal) {
                case null:
                    // Fetch all data
                    $dataBeritaQuery = $this->getDataBeritaQuery($request);
                    $dataFotoQuery = $this->getDataFotoQuery($request);
                    $dataVideoQuery = $this->getDataVideoQuery($request);
                    $dataSerbaSerbiQuery = $this->getDataSerbaSerbiQuery($request);

                    $finalQuery = " SELECT * FROM (
                                        $dataBeritaQuery 
                                        UNION ALL 
                                        $dataFotoQuery 
                                        UNION ALL 
                                        $dataVideoQuery 
                                        UNION ALL 
                                        $dataSerbaSerbiQuery) as data 
                                    $orderByQuery";
                    break;
                case 'berita':
                    // Fetch data for kanal 0 (getDataBeritaQuery)
                    $dataBeritaQuery = $this->getDataBeritaQuery($request);

                    $finalQuery = "SELECT * FROM ($dataBeritaQuery) as data $orderByQuery";
                    break;
                case 'foto':
                    // Fetch data for kanal 1 (getDataFotoQuery)
                    $dataFotoQuery = $this->getDataFotoQuery($request);

                    $finalQuery = "SELECT * FROM ($dataFotoQuery) as data $orderByQuery";
                    break;
                case 'video':
                    // Fetch data for kanal 2 (getDataVideoQuery)
                    $dataVideoQuery = $this->getDataVideoQuery($request);

                    $finalQuery = "SELECT * FROM ($dataVideoQuery) as data $orderByQuery";
                    break;
                case 'serba-serbi':
                    // Fetch data for kanal 3 (getDataSerbaSerbiQuery)
                    $dataSerbaSerbiQuery = $this->getDataSerbaSerbiQuery($request);

                    $finalQuery = "SELECT * FROM ($dataSerbaSerbiQuery) as data $orderByQuery";
                    break;
                default:
                    // Handle other cases if needed
                    break;
            }

            $countData = DB::select("SELECT COUNT(*) as total FROM ($finalQuery) as data", $this->getQueryBindings($request, true));

            $data = DB::select($finalQuery . " $paginationQuery", $this->getQueryBindings($request, false));

            foreach ($data as $value) {
                $value->tanggal_publikasi = Carbon::createFromFormat('Y-m-d H:i:s', $value->tanggal_publikasi)->locale("id")->translatedFormat('l, j F Y');
            }

            return response()->json([
                'data' => $data,
                'totalLength' => $countData[0]->total
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'pesan' => "Terjadi kesalahan pada server.",
                'error' => $th->getMessage()
            ], 500);
        }
    }

    private function getDataBeritaQuery(Request $request)
    {
        try {
            $istana = $request->istana;
            $tanggalPublikasi = $request->tanggal;

            $whereIstana = "";
            $whereTanggalPublikasi = "";
        
            if (isset($istana)) {
                $whereIstana .= " AND tb.i_id_istana = :istana1";
            }
            if (isset($tanggalPublikasi)) {
                $whereTanggalPublikasi .= " AND DATE(tb.d_publish_date) = :tanggalPublikasi1";
            }
        
            $sql = "SELECT 
                        tb.i_id_berita as id,
                        tb.i_id_istana as id_istana,
                        tb.n_judul as judul,
                        tb.d_publish_date as tanggal_publikasi,
                        'Berita' as kategori 
                    FROM 
                        tm_berita tb
                    WHERE 
                        (LOWER(tb.n_judul) LIKE LOWER(:kataCari1) OR
                        LOWER(tb.e_berita) LIKE LOWER(:kataCari2))
                    $whereIstana
                    $whereTanggalPublikasi";
        
            return $sql;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new \Exception($th->getMessage(), $th->getCode());
        }        
    }

    private function getDataFotoQuery(Request $request)
    {
        try {
            $tanggalPublikasi = $request->tanggal;

            $whereTanggalPublikasi = "";

            if(isset($tanggalPublikasi)) {
                $whereTanggalPublikasi = " AND DATE(tm.d_publish_date) = :tanggalPublikasi2";
            } 

            $sql = "SELECT
                        tm.i_id_media as id,
                        null as id_istana,
                        tm.n_judul as judul,
                        tm.d_publish_date as tanggal_publikasi,
                        'Foto' as kategori
                    FROM tm_media tm
                    WHERE (LOWER(tm.n_judul) LIKE LOWER(:kataCari3) OR 
                        LOWER(tm.e_keterangan) LIKE LOWER(:kataCari4))
                    AND tm.c_media = 0
                    AND tm.c_is_publish = 1
                    $whereTanggalPublikasi";

            return $sql;   
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private function getDataVideoQuery(Request $request)
    {
        try {
            $tanggalPublikasi = $request->tanggal;

            $whereTanggalPublikasi = "";

            if(isset($tanggalPublikasi)) {
                $whereTanggalPublikasi = " AND DATE(tm.d_publish_date) = :tanggalPublikasi3";
            } 

            $sql = "SELECT
                        tm.i_id_media as id,
                        null as id_istana,
                        tm.n_judul as judul,
                        tm.d_publish_date as tanggal_publikasi,
                        'Video' as kategori
                    FROM tm_media tm
                    WHERE (LOWER(tm.n_judul) LIKE LOWER(:kataCari5) OR 
                        LOWER(tm.e_keterangan) LIKE LOWER(:kataCari6))
                    AND tm.c_media = 1
                    AND tm.c_is_publish = 1
                    $whereTanggalPublikasi";

            return $sql;   
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private function getDataSerbaSerbiQuery(Request $request)
    {
        try {
            $istana = $request->istana;
            $tanggalPublikasi = $request->tanggal;
    
            $whereIstana = "";
            $whereTanggalPublikasi = "";
    
            if(isset($istana)) {
                $whereIstana = " AND tb.i_id_istana = :istana2";
            }
            if(isset($tanggalPublikasi)) {
                $whereTanggalPublikasi = " AND DATE(tb.d_publish_date) = :tanggalPublikasi4";
            }
    
            $sql = "SELECT 
                        tb.i_id as id,
                        tb.i_id_istana as id_istana,
                        tb.n_nama_bangunan as judul,
                        tb.d_publish_date as tanggal_publikasi,
                        'Serba-serbi' as kategori 
                    FROM 
                        tm_bangunan tb
                    WHERE 
                        LOWER(tb.n_nama_bangunan) LIKE LOWER(:kataCari7)
                    AND 
                        tb.c_status = 1
                    $whereIstana
                    $whereTanggalPublikasi";
    
            return $sql;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private function getQueryBindings(Request $request, $isCount = false)
    {
        $istana = $request->istana;
        $tanggalPublikasi = $request->tanggal;

        switch($request->kanal) {
            case null:
                $bindings = [
                    'kataCari1' => '%' . $request->q . '%',
                    'kataCari2' => '%' . $request->q . '%',
                    'kataCari3' => '%' . $request->q . '%',
                    'kataCari4' => '%' . $request->q . '%',
                    'kataCari5' => '%' . $request->q . '%',
                    'kataCari6' => '%' . $request->q . '%',
                    'kataCari7' => '%' . $request->q . '%'
                ];

                if (isset($istana)) {
                    $bindings['istana1'] = $istana;
                    $bindings['istana2'] = $istana;  
                }

                if (isset($tanggalPublikasi)) {
                    $bindings['tanggalPublikasi1'] = $tanggalPublikasi;
                    $bindings['tanggalPublikasi2'] = $tanggalPublikasi;
                    $bindings['tanggalPublikasi3'] = $tanggalPublikasi;
                    $bindings['tanggalPublikasi4'] = $tanggalPublikasi;
                }

                break;
            case 'berita':
                $bindings = [
                    'kataCari1' => '%' . $request->q . '%',
                    'kataCari2' => '%' . $request->q . '%'
                ];

                if (isset($istana)) {
                    $bindings['istana1'] = $istana;  
                }

                if (isset($tanggalPublikasi)) {
                    $bindings['tanggalPublikasi1'] = $tanggalPublikasi;
                }

                break;
            case 'foto':
                $bindings = [
                    'kataCari3' => '%' . $request->q . '%',
                    'kataCari4' => '%' . $request->q . '%'
                ];

                if (isset($tanggalPublikasi)) {
                    $bindings['tanggalPublikasi2'] = $tanggalPublikasi;
                }
                break;
            case 'video':
                $bindings = [
                    'kataCari5' => '%' . $request->q . '%',
                    'kataCari6' => '%' . $request->q . '%'
                ];

                if (isset($tanggalPublikasi)) {
                    $bindings['tanggalPublikasi3'] = $tanggalPublikasi;
                }
                break;
            case 'serba-serbi':
                $bindings = [
                    'kataCari7' => '%' . $request->q . '%'
                ];

                if (isset($istana)) {
                    $bindings['istana2'] = $istana;  
                }

                if (isset($tanggalPublikasi)) {
                    $bindings['tanggalPublikasi4'] = $tanggalPublikasi;
                }
                break;
            default:
                $bindings = [];
                break;
        }

        if (!$isCount) {
            $bindings['limit'] = $request->limit;
            $bindings['offset'] = $request->offset;
        }

        return $bindings;
    }
}
