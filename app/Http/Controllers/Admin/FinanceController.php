<?php

namespace App\Http\Controllers\Admin;

use App\Finance\Ledger;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PatientPackage;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** O dinheiro que entra: atendimentos avulsos e pacotes vendidos, mês a mês. */
class FinanceController extends Controller
{
    public const METHODS = ['pix' => 'Pix', 'dinheiro' => 'Dinheiro', 'cartao' => 'Cartão', 'transferencia' => 'Transferência'];

    public function index(Request $request): View
    {
        $month = $this->month($request->query('mes'));
        $entries = $this->entries($month);
        $filter = $request->query('filtro');

        $shown = match ($filter) {
            'a-receber' => $entries->where('paid', false)->where('due', true),
            'sem-nota' => $entries->where('paid', true)->where('invoiced', false),
            default => $entries,
        };

        return view('admin.finance', [
            'month' => $month,
            'entries' => $shown->values(),
            'filter' => $filter,
            'methods' => self::METHODS,
            'totals' => [
                'received' => $entries->where('paid', true)->sum('cents'),
                'due' => $entries->where('paid', false)->where('due', true)->sum('cents'),
                'expected' => $entries->where('paid', false)->where('due', false)->sum('cents'),
                'without_invoice' => $entries->where('paid', true)->where('invoiced', false)->count(),
            ],
        ]);
    }

    public function update(Request $request, string $kind, int $id): RedirectResponse
    {
        $record = match ($kind) {
            'atendimento' => Appointment::query()->findOrFail($id),
            'pacote' => PatientPackage::query()->findOrFail($id),
            default => abort(404),
        };

        $data = $request->validate([
            'price' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['nullable', 'in:'.implode(',', array_keys(self::METHODS))],
            'invoice_number' => ['nullable', 'string', 'max:40'],
        ]);

        $invoice = trim((string) ($data['invoice_number'] ?? ''));
        $record->update([
            'price_cents' => Money::parse($data['price'] ?? null),
            'paid_at' => $request->boolean('paid') ? ($record->paid_at ?? now()) : null,
            'payment_method' => $request->boolean('paid') ? ($data['payment_method'] ?? null) : null,
            'invoice_number' => $invoice !== '' ? $invoice : null,
            'invoiced_at' => $invoice !== '' ? ($record->invoiced_at ?? now()) : null,
        ]);

        return back()->with('ok', 'Lançamento atualizado.');
    }

    /** Planilha do mês, para ela ou o contador. */
    public function export(Request $request): StreamedResponse
    {
        $month = $this->month($request->query('mes'));
        $entries = $this->entries($month);

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // para o Excel abrir os acentos certos
            fputcsv($out, ['Data', 'Paciente', 'Responsável', 'Descrição', 'Valor', 'Pago em', 'Forma', 'Nota fiscal'], ';');
            foreach ($entries as $e) {
                fputcsv($out, [
                    $e['date']->format('d/m/Y'), $e['patient'], $e['guardian'], $e['description'],
                    number_format($e['cents'] / 100, 2, ',', ''), $e['paid_at']?->format('d/m/Y') ?? '',
                    self::METHODS[$e['method']] ?? '', $e['invoice'] ?? '',
                ], ';');
            }
            fclose($out);
        }, 'financeiro-'.$month->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function month(?string $value): CarbonImmutable
    {
        return $value && preg_match('/^\d{4}-\d{2}$/', $value)
            ? CarbonImmutable::createFromFormat('Y-m-d', $value.'-01')->startOfDay()
            : CarbonImmutable::today()->startOfMonth();
    }

    private function entries(CarbonImmutable $month): Collection
    {
        return Ledger::entries($month);
    }
}
