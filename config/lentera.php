<?php

return [
    'demo_enabled' => (bool) env('LENTERA_DEMO_ENABLED', false),
    // Suggestions only; participants may type another category.
    'participant_categories' => ['Mahasiswa', 'GenBI', 'Dosen', 'Akademisi', 'Pegawai', 'Lainnya'],
    // Working copy of DESIGN V2; official wording awaits approval.
    'l1_questions' => [
        'Kejelasan isi: Menurut Bapak/Ibu, bagaimana pemahaman Bapak/Ibu terhadap materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026?',
        'Kejelasan isi: Penjelasan materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026 tidak menimbulkan ambiguitas.',
        'Keterbacaan: Menurut Bapak/Ibu, seberapa mudah istilah pada materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026 yang disampaikan untuk bisa dipahami?',
        'Interpretasi: Menurut Bapak/Ibu, apakah materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026 yang disajikan dapat untuk ditarik kesimpulan secara tepat?',
        'Peningkatan Pengetahuan: Saya mendapatkan pemahaman/pengetahuan baru setelah membaca materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026.',
    ],
];
