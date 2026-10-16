<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class L2Instrument extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['questions', 'creation_key', 'creation_hash'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'published_at' => 'datetime', 'revision' => 'integer'];
    }

    public function hasValidQuestions(): bool
    {
        $questions = $this->questions;
        // Authoring limits must not invalidate a previously published version.
        if (! is_array($questions) || ! array_is_list($questions) || count($questions) < 1) {
            return false;
        }
        foreach ($questions as $question) {
            if (! is_array($question) || ! is_string($question['text'] ?? null) || trim($question['text']) === '' || mb_strlen($question['text']) > 2000
                || ! is_array($question['options'] ?? null) || array_keys($question['options']) !== [0, 1, 2, 3]
                || ! is_int($question['correct'] ?? null) || $question['correct'] < 0 || $question['correct'] > 3) {
                return false;
            }
            foreach ($question['options'] as $option) {
                if (! is_string($option) || trim($option) === '' || mb_strlen($option) > 500) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function publicQuestions(array $questions): array
    {
        return array_map(fn ($question) => ['text' => $question['text'], 'options' => $question['options']], $questions);
    }

    protected static function booted(): void
    {
        static::updating(function ($instrument) {
            if ($instrument->getOriginal('published_at')) {
                throw new \LogicException('Instrumen L2 terbit terkunci; buat versi baru.');
            }
            if ($instrument->isDirty(['office_id', 'code', 'name', 'version', 'creation_key', 'creation_hash'])) {
                throw new \LogicException('Identitas versi L2 tidak boleh diubah.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Versi instrumen L2 tidak boleh dihapus.'));
    }
}
