<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialVersion extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['storage_path', 'upload_key'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'material_id' => 'integer', 'version_number' => 'integer', 'size_bytes' => 'integer'];
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Versi materi tidak boleh ditimpa; unggah versi baru.'));
        static::deleting(fn () => throw new \LogicException('Versi materi tidak boleh dihapus melalui model.'));
    }
}
