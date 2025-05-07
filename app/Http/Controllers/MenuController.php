<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function getActiveMenu()
    {
        $galeri = DB::select("SELECT
                                tmenu.i_id_menu as id_menu,
                                trm.n_keterangan as nama_menu,
                                tmenu.i_id_submenu as id_submenu,
                                trs.n_keterangan as nama_submenu,
                                tmenu.n_link as link
                              FROM
                                tm_menu tmenu
                              JOIN
                                tr_menu trm ON trm.i_id = tmenu.i_id_menu
                              JOIN
                                tr_submenu trs ON trs.i_id = tmenu.i_id_submenu                                      
                              WHERE
                                tmenu.c_is_active = 1
                              AND
                                tmenu.i_id_menu = 1");

        $pendaftaran = DB::select("SELECT
                                tmenu.i_id_menu as id_menu,
                                trm.n_keterangan as nama_menu,
                                tmenu.i_id_submenu as id_submenu,
                                trs.n_keterangan as nama_submenu,
                                tmenu.n_link as link
                              FROM
                                tm_menu tmenu
                              JOIN
                                tr_menu trm ON trm.i_id = tmenu.i_id_menu
                              JOIN
                                tr_submenu trs ON trs.i_id = tmenu.i_id_submenu                                      
                              WHERE
                                tmenu.c_is_active = 1
                              AND
                                tmenu.i_id_menu = 2");
        
        $layanan = DB::select("SELECT
                                tmenu.i_id_menu as id_menu,
                                trm.n_keterangan as nama_menu,
                                tmenu.i_id_submenu as id_submenu,
                                trs.n_keterangan as nama_submenu,
                                tmenu.n_link as link
                              FROM
                                tm_menu tmenu
                              JOIN
                                tr_menu trm ON trm.i_id = tmenu.i_id_menu
                              JOIN
                                tr_submenu trs ON trs.i_id = tmenu.i_id_submenu                                      
                              WHERE
                                tmenu.c_is_active = 1
                              AND
                                tmenu.i_id_menu = 3");

        $app = DB::select("SELECT
                    tapp.n_nama,
                    tapp.c_icon,
                    tapp.n_link
                FROM
                    tr_app tapp
                WHERE
                    tapp.c_show_in_public = 1");
        
        $token = array(
            "galeri" => $galeri,
            "pendaftaran" => $pendaftaran,
            "layanan" => $layanan,
            "app" => $app
        );

        return response()->json($token);
    }
}
