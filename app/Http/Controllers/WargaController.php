<?php

namespace App\Http\Controllers;

use App\Models\Warga;
use App\Models\Dusun;
use App\Models\Keluarga;
use App\Models\DetailKeluarga;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Datatables;

class WargaController extends Controller
{
     public function __construct()
    {
        $this->warga = new Warga();
        $this->dusun = new Dusun();
        $this->keluargas = new Keluarga();
        $this->detailKeluarga = new DetailKeluarga();
    }

    public function index()
    {
        if (request()->ajax()) {
            return datatables()->of($this->warga->getFullData())
            ->addIndexColumn()
            ->make(true);
        }

        $data = [
            'title' => "Data penduduk",
            'dusuns' => $this->dusun->dusuns()
        ];

        return view('pages.wargas', $data);
    }

     public function create()
    {
        $attributes = request()->validate([
            'no_ktp' => 'required|numeric|digits:16|unique:wargas,no_ktp',
            'nama_lengkap' => 'required|max:64|min:2|max:255',
            'agama' => 'required|alpha|max:255',
            'tempat_lahir' => 'required|max:255',
            'tgl_lahir' => 'required|max:255',
            'jenis_kelamin' => 'required|max:255',
            'alamat' => 'required|max:255',
            'dusun' => 'required|max:255',
            'golongan_darah' => 'required|max:255',
            'warga_negara' => 'required|max:255',
            'pendidikan' => 'required|max:255',
            'baca_tulis' => 'required|max:255',
            'pekerjaan' => 'required|max:255',
            'status_nikah' => 'required|max:255',
            'status_warga' => 'required',
        ]);

        $post = Warga::create($attributes);

        return response()->json(['message'=>'Data berhasil di simpan.']);
    }

    public function show($id){
       // $data = Warga::find($id);
        $data = $this->warga->getFullData($id);
        return response()->json($data);
    }

    public function update(Request $request)
    {
        $request->validate([
            'no_ktp' => [
                'required',
                'digits:16',
                Rule::unique('wargas', 'no_ktp')->ignore($request->id),
            ],
            'nama_lengkap' => 'required|max:64|min:2|max:255',
            'agama' => 'required|alpha|max:255',
            'tempat_lahir' => 'required|max:255',
            'tgl_lahir' => 'required|max:255',
            'jenis_kelamin' => 'required|max:255',
            'alamat' => 'required|max:255',
            'dusun' => 'required|max:255',
            'golongan_darah' => 'required|max:255',
            'warga_negara' => 'required|max:255',
            'pendidikan' => 'required|max:255',
            'baca_tulis' => 'required|max:255',
            'pekerjaan' => 'required|max:255',
            'status_nikah' => 'required|max:255',
            'status_warga' => 'required',
        ]);

        $update = Warga::find($request->id)->update($request->all());

        return response()->json(['message'=>'Data berhasil diperbarui']);
    }

    public function delete($id)
    {    
        $data = $this->warga->getFullData($id);
            
        if($data->no_kk != null){
            
            $detailKeluarga = $this->detailKeluarga->where(['keluarga_id' => $data->keluarga_id])->get();
            
            if(($detailKeluarga->count() <= 1) && ($data->status_anggota == "Kepala Keluarga")){
                DB::transaction(function() use ($id, $data) {
                    $this->detailKeluarga->where(['keluarga_id' => $data->keluarga_id])->forceDelete();
                    $this->keluargas->find($data->keluarga_id)->forceDelete();
                    $delete = $this->warga->find($id);
                    $delete->forceDelete();
                });
                return response()->json(['message'=>'Data berhasil dihapus','is_delete'=> true]);
            }
            
            if($data->status_anggota == "Kepala Keluarga"){
                $message = "Tidak dapat menghapus data ".$data->nama_lengkap ." dikarenakan berstatus sebagai 'Kepala Keluarga' untuk nomor Kartu Keluarga ". $data->no_kk;
                return response()->json(['message'=> $message,'is_delete'=> false]);
            }
            
            if($detailKeluarga->count() <= 1){
                DB::transaction(function() use ($id, $data) {
                    $this->detailKeluarga->where(['keluarga_id' => $data->keluarga_id])->forceDelete();
                    $this->keluargas->find($data->keluarga_id)->forceDelete();  
                });
            }
        }
        
        $delete = $this->warga->find($id);
        $delete->forceDelete();

        return response()->json(['message'=>'Data berhasil dihapus','is_delete'=> true]);
    }
}
