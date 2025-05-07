<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Istura extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table="tm_istura";
    protected $fillable = [
        'i_id_istura',
        'i_id_istana',
        'n_rombongan',
        'n_penanggung_jawab',
        'c_telpon',
        'c_kategori',
        'd_tanggal_kedatangan',
        'd_jam_kedatangan',
        'c_jumlah_peserta',
        'c_file_peserta',
        'c_no_permohonan',
        'd_tanggal_surat',
        'c_file_surat_permohonan',
        'c_status',
        'd_created_date',
        'i_id_pemandu'
    ];
}
