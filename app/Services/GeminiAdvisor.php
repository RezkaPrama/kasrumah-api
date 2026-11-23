<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Memanggil Gemini (REST) untuk saran alokasi saldo rumah tangga.
 * Prompt dan skema JSON diporting dari server.ts aplikasi AI Studio.
 */
class GeminiAdvisor
{
    private const SYSTEM_INSTRUCTION =
        'Anda adalah konsultan keuangan keluarga Indonesia yang akurat, membumi, dan selalu menghitung angka dalam Rupiah (IDR) secara konsisten.';

    /**
     * @param  array{
     *   monthLabel: string, monthlyIncome: int, routineTotal: int, dailyTotal: int, remainingBalance: int,
     *   focusGoal: string, customNote: string,
     *   routineItems: array<int, array{name: string, amount: int, categoryName: string, isActive: bool, isPaid: bool}>,
     *   categories: array<int, array{name: string, limit: int, spent: int}>
     * }  $ctx
     * @return array<string, mixed>
     */
    public function advise(array $ctx): array
    {
        $key = config('kasrumah.gemini.key');
        if (! $key) {
            throw new RuntimeException('GEMINI_API_KEY belum diatur di server.', 500);
        }

        $model = config('kasrumah.gemini.model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $payload = [
            'systemInstruction' => ['parts' => [['text' => self::SYSTEM_INSTRUCTION]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $this->buildPrompt($ctx)]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->schema(),
            ],
        ];

        $response = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = Http::withHeaders(['x-goog-api-key' => $key])
                ->acceptJson()
                ->timeout((int) config('kasrumah.gemini.timeout', 60))
                ->post($url, $payload);

            if (in_array($response->status(), [429, 503], true) && $attempt < 3) {
                usleep(800_000 * $attempt);

                continue;
            }
            break;
        }

        if (! $response->successful()) {
            $message = $response->json('error.message') ?: 'Layanan AI tidak merespons. Coba lagi sebentar lagi.';
            throw new RuntimeException($message, $response->status());
        }

        $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));
        if ($text === '') {
            throw new RuntimeException('Respons AI kosong. Silakan coba lagi.', 502);
        }

        $parsed = json_decode($text, true);
        if (! is_array($parsed)) {
            throw new RuntimeException('Respons AI tidak dapat dibaca. Silakan coba lagi.', 502);
        }

        return $this->normalize($parsed);
    }

    private function rp(int|float $n): string
    {
        return number_format((float) $n, 0, ',', '.');
    }

    private function buildPrompt(array $c): string
    {
        $routines = collect($c['routineItems'])
            ->filter(fn ($r) => $r['isActive'])
            ->map(fn ($r) => sprintf(
                '- %s (%s): Rp %s [%s]',
                $r['name'],
                $r['categoryName'],
                $this->rp($r['amount']),
                $r['isPaid'] ? 'Sudah bayar' : 'Belum bayar'
            ))
            ->implode("\n") ?: '- Belum ada pengeluaran rutin aktif';

        $categories = collect($c['categories'])
            ->map(function ($cat) {
                $pct = $cat['limit'] > 0 ? (int) round($cat['spent'] / $cat['limit'] * 100) : 0;

                return sprintf(
                    '- %s: Terpakai Rp %s dari batas Rp %s (%d%%)',
                    $cat['name'],
                    $this->rp($cat['spent']),
                    $this->rp($cat['limit']),
                    $pct
                );
            })
            ->implode("\n") ?: '- Belum ada kategori';

        $total = $c['routineTotal'] + $c['dailyTotal'];
        $focus = $c['focusGoal'] ?: 'Prioritas Tabungan & Tambahan Pemasukan';
        $note = trim($c['customNote']) ?: 'Tidak ada catatan khusus';
        $remaining = $this->rp($c['remainingBalance']);

        return <<<PROMPT
Analisis kondisi anggaran rumah tangga Indonesia untuk bulan {$c['monthLabel']}:
- Pendapatan Bulanan: Rp {$this->rp($c['monthlyIncome'])}
- Total Pengeluaran Rutin Aktif: Rp {$this->rp($c['routineTotal'])}
- Total Pengeluaran Harian: Rp {$this->rp($c['dailyTotal'])}
- Total Pengeluaran: Rp {$this->rp($total)}
- Sisa Saldo Tersisa: Rp {$remaining}

Daftar Pengeluaran Rutin:
{$routines}

Status Batas Kategori Anggaran:
{$categories}

Fokus Pengguna: {$focus}
Catatan Tambahan Pengguna: {$note}

Tugas Anda sebagai perencana keuangan rumah tangga yang praktis dan realistis:
1. Evaluasi singkat kondisi arus kas dan sisa saldo saat ini dalam bahasa Indonesia yang ramah, jelas, dan langsung ke inti.
2. Buat rencana alokasi Sisa Saldo Tersisa (Rp {$remaining}) yang MEMPRIORITASKAN TABUNGAN (misal Dana Darurat, Tabungan Pendidikan Anak, Investasi Aman, dan Cadangan Tak Terduga). Pastikan jumlah nominal alokasi masuk akal dan totalnya tidak melebihi Sisa Saldo Tersisa (jika saldo <= 0, berikan langkah pemulihan penghematan konkrit dengan nominal 0 pada alokasi tabungan).
3. Berikan 3 ide realistis untuk MENDAPATKAN TAMBAHAN PENGHASILAN / IMBAL HASIL dari sisa saldo yang ada (misalnya produk pasar uang syariah/konvensional resmi OJK, emas digital, atau usaha sampingan rumahan bermodal kecil sesuai sisa saldo yang tersedia, lengkap dengan langkah mulai nyata).
4. Berikan saran optimasi per kategori yang mendekati atau melewati batas anggaran maupun pengeluaran rutin (SPP, Tahfidz, Kumon, Bensin, Listrik, Katering, dll).
PROMPT;
    }

    private function schema(): array
    {
        $str = ['type' => 'STRING'];
        $num = ['type' => 'NUMBER'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'statusKeuangan' => [
                    'type' => 'STRING',
                    'description' => 'Label status singkat seperti "Surplus Sehat", "Perlu Pengetatan", atau "Defisit Anggaran".',
                ],
                'ringkasanKondisi' => [
                    'type' => 'STRING',
                    'description' => 'Ringkasan evaluasi arus kas rumah tangga 2-3 kalimat.',
                ],
                'alokasiTabungan' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['pos' => $str, 'nominal' => $num, 'persentase' => $num, 'alasan' => $str],
                        'required' => ['pos', 'nominal', 'persentase', 'alasan'],
                    ],
                ],
                'idePendapatanTambahan' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'judul' => $str,
                            'modalDisarankan' => $num,
                            'potensiTambahan' => $str,
                            'langkahMulai' => $str,
                            'tingkatRisiko' => $str,
                        ],
                        'required' => ['judul', 'modalDisarankan', 'potensiTambahan', 'langkahMulai', 'tingkatRisiko'],
                    ],
                ],
                'optimasiKategori' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['kategori' => $str, 'catatan' => $str, 'aksiDisarankan' => $str],
                        'required' => ['kategori', 'catatan', 'aksiDisarankan'],
                    ],
                ],
            ],
            'required' => ['statusKeuangan', 'ringkasanKondisi', 'alokasiTabungan', 'idePendapatanTambahan', 'optimasiKategori'],
        ];
    }

    /** Pastikan bentuk respons selalu konsisten untuk aplikasi mobile. */
    private function normalize(array $r): array
    {
        return [
            'statusKeuangan' => (string) ($r['statusKeuangan'] ?? ''),
            'ringkasanKondisi' => (string) ($r['ringkasanKondisi'] ?? ''),
            'alokasiTabungan' => array_values(array_map(fn ($i) => [
                'pos' => (string) ($i['pos'] ?? ''),
                'nominal' => (int) round((float) ($i['nominal'] ?? 0)),
                'persentase' => (float) ($i['persentase'] ?? 0),
                'alasan' => (string) ($i['alasan'] ?? ''),
            ], (array) ($r['alokasiTabungan'] ?? []))),
            'idePendapatanTambahan' => array_values(array_map(fn ($i) => [
                'judul' => (string) ($i['judul'] ?? ''),
                'modalDisarankan' => (int) round((float) ($i['modalDisarankan'] ?? 0)),
                'potensiTambahan' => (string) ($i['potensiTambahan'] ?? ''),
                'langkahMulai' => (string) ($i['langkahMulai'] ?? ''),
                'tingkatRisiko' => (string) ($i['tingkatRisiko'] ?? ''),
            ], (array) ($r['idePendapatanTambahan'] ?? []))),
            'optimasiKategori' => array_values(array_map(fn ($i) => [
                'kategori' => (string) ($i['kategori'] ?? ''),
                'catatan' => (string) ($i['catatan'] ?? ''),
                'aksiDisarankan' => (string) ($i['aksiDisarankan'] ?? ''),
            ], (array) ($r['optimasiKategori'] ?? []))),
        ];
    }
}
