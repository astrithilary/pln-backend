<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelangganController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi data yang masuk dari Flutter
        $validated = $request->validate([
            'nama' => 'required',
            'alamat' => 'required',
            'no_meter' => 'nullable|string',
            'id_pelanggan' => 'nullable|string',
            'daya_listrik' => 'nullable|integer',
            'daya' => 'nullable|integer',
            'no_hp' => 'nullable|string',
            'foto_path' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'waktu_kunjungan' => 'nullable|date',
        ]);

        // Normalize data
        $no_meter = $request->no_meter ?? $request->id_pelanggan ?? '';
        $daya_listrik = $request->daya_listrik ?? $request->daya;

        // 2. Simpan ke database SQLite Laptop
        DB::table('pelanggans')->insert([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'no_meter' => $no_meter,
            'daya_listrik' => $daya_listrik,
            'no_hp' => $request->no_hp,
            'foto_path' => $request->foto_path,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'waktu_kunjungan' => $request->waktu_kunjungan ? \Carbon\Carbon::parse($request->waktu_kunjungan) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Beri respon ke Flutter kalau berhasil
        return response()->json([
            'status' => 'success',
            'message' => 'Data pelanggan berhasil disinkronkan ke server!'
        ], 201);
    }

    public function uploadFoto(Request $request, $id)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'pelanggan_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('pelanggan_fotos', $filename, 'public');

            // Update foto_path di database
            DB::table('pelanggans')->where('id', $id)->update([
                'foto_path' => $path,
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'foto_path' => $path,
                'message' => 'Foto berhasil diupload!'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'File foto tidak ditemukan'
        ], 400);
    }
}