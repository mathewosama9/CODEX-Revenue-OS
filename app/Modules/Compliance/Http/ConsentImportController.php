<?php

namespace App\Modules\Compliance\Http;

use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Models\ConsentImport;
use App\Shared\Phone\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The merchant's consent source for recovery (review B1): a CSV of customers
 * who agreed to WhatsApp marketing, with the exact wording they agreed to and
 * the owner's attestation. [افتراض] The attestation text needs legal review.
 */
class ConsentImportController
{
    /** Bumped whenever the attestation wording in the app changes. */
    public const ATTESTATION_VERSION = '2026-10-26';

    private const MAX_ROWS = 20_000;

    /** Header names we recognise for the phone column (Salla exports, Arabic sheets). */
    private const PHONE_HEADERS = ['phone', 'mobile', 'phone_number', 'mobile_number', 'whatsapp', 'جوال', 'الجوال', 'رقم الجوال', 'الهاتف', 'رقم الهاتف'];

    public function index(): JsonResponse
    {
        return response()->json(['data' => ConsentImport::query()->latest('id')->limit(50)->get()->map(fn (ConsentImport $import) => [
            'id' => $import->id,
            'collected_via' => $import->collected_via,
            'rows_total' => $import->rows_total,
            'granted' => $import->granted,
            'already_granted' => $import->already_granted,
            'skipped_opted_out' => $import->skipped_opted_out,
            'skipped_invalid' => $import->skipped_invalid,
            'created_at' => $import->created_at->toIso8601String(),
        ])->values()]);
    }

    public function store(Request $request, ConsentGate $consent): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel'],
            'consent_text' => ['required', 'string', 'min:20', 'max:2000'],
            'collected_via' => ['required', Rule::in(['checkout', 'website_form', 'in_store', 'whatsapp', 'other'])],
            'attested' => ['accepted'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        [$phones, $invalid] = $this->phones($file);

        if ($phones === [] && $invalid === 0) {
            throw ValidationException::withMessages(['file' => __('The file has no phone numbers.')]);
        }

        $reference = (string) Str::ulid();
        $text = trim($validated['consent_text']);
        $evidence = [
            'import' => $reference,
            'collected_via' => $validated['collected_via'],
            'consent_text_sha256' => hash('sha256', $text),
            'attestation_version' => self::ATTESTATION_VERSION,
            'attested_by' => $request->user()?->id,
        ];

        $import = DB::transaction(function () use ($consent, $phones, $evidence, $reference, $request, $file, $text, $validated, $invalid) {
            $counts = $consent->importGrants($phones, $evidence);

            return ConsentImport::query()->create($counts + [
                'reference' => $reference,
                'user_id' => $request->user()?->id,
                'file_sha256' => hash_file('sha256', $file->getRealPath()) ?: '',
                'consent_text' => $text,
                'consent_text_sha256' => $evidence['consent_text_sha256'],
                'collected_via' => $validated['collected_via'],
                'attestation_version' => self::ATTESTATION_VERSION,
                'rows_total' => count($phones) + $invalid,
                'skipped_invalid' => $invalid,
            ]);
        });

        return response()->json(['data' => [
            'id' => $import->id,
            'rows_total' => $import->rows_total,
            'granted' => $import->granted,
            'already_granted' => $import->already_granted,
            'skipped_opted_out' => $import->skipped_opted_out,
            'skipped_invalid' => $import->skipped_invalid,
        ]], 201);
    }

    /**
     * Reads the phone column (by header name, else the first column). The file
     * is read once and never stored.
     *
     * @return array{0: list<PhoneNumber>, 1: int} valid phones, invalid row count
     */
    private function phones(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => __('The file could not be read.')]);
        }

        $phones = [];
        $invalid = 0;
        $column = 0;
        $row = 0;

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $row++;

            if ($row === 1) {
                $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]); // Excel's UTF-8 BOM
                $headers = array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), $cells);
                $match = array_search(true, array_map(fn ($header) => in_array($header, self::PHONE_HEADERS, true), $headers), true);

                if ($match !== false) {
                    $column = (int) $match;

                    continue;
                }

                // No recognisable header: the first row is data, phone in the first column.
            }

            if (count($phones) + $invalid >= self::MAX_ROWS) {
                fclose($handle);

                throw ValidationException::withMessages(['file' => __('Up to :max rows per file.', ['max' => self::MAX_ROWS])]);
            }

            $value = trim((string) ($cells[$column] ?? ''));

            if ($value === '') {
                continue;
            }

            $phone = PhoneNumber::tryParse($value);

            if ($phone === null) {
                $invalid++;
            } else {
                $phones[] = $phone;
            }
        }

        fclose($handle);

        return [$phones, $invalid];
    }
}
