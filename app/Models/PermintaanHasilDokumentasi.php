<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanHasilDokumentasi extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table="permintaan_hasil_dokumentasi";
    protected $fillable = [
        'istana_kepresidenan',
        'rombongan',
        'penanggung_jawab',
        'telp_penanggung_jawab',
        'kategori_rombongan',
        'tgl_jam_kunjungan',
        'jumlah_peserta',
        'jenis_dokumentasi',
        'file_bukti_kunjungan'
    ];

}
