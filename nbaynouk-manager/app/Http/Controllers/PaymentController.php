<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\PaymentRequest;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Services\ActivityLogService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()->with(['project.business.client', 'billingPeriod'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('reference', 'like', '%'.$request->string('search').'%')->orWhereHas('project', fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('client_id'), fn ($q) => $q->whereHas('project.business', fn ($q) => $q->where('client_id', $request->integer('client_id'))))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->when($request->filled('month'), fn ($q) => $q->whereYear('payment_date', substr($request->string('month'), 0, 4))->whereMonth('payment_date', substr($request->string('month'), 5, 2)))
            ->orderByDesc('payment_date')->paginate(20)->withQueryString();
        $receivedThisMonth = Payment::whereBetween('payment_date', [today()->startOfMonth(), today()->endOfMonth()])->sum('amount');
        $periods = BillingPeriod::with('payments')->get();
        $outstanding = $periods->reduce(fn (string $sum, BillingPeriod $period) => bcadd($sum, $period->remaining_amount, 2), '0.00');
        $overdue = $periods->filter(fn ($period) => $period->payment_status->value === 'overdue')->reduce(fn (string $sum, BillingPeriod $period) => bcadd($sum, $period->remaining_amount, 2), '0.00');
        $projects = Project::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        $methods = PaymentMethod::cases();

        return view('payments.index', compact('payments', 'receivedThisMonth', 'outstanding', 'overdue', 'projects', 'clients', 'methods'));
    }

    public function create(Request $request): View
    {
        $projects = Project::with('billingPeriods')->orderBy('name')->get();
        $methods = PaymentMethod::cases();
        $selectedProject = $request->integer('project_id');

        return view('payments.form', compact('projects', 'methods', 'selectedProject'));
    }

    public function store(PaymentRequest $request, ActivityLogService $activity): RedirectResponse|JsonResponse
    {
        $payment = DB::transaction(function () use ($request, $activity) {
            $payment = Payment::create($request->validated());
            $activity->record($payment->project, 'payment_created', 'Paiement de '.Money::format($payment->amount).' enregistré.', ['payment_id' => $payment->id]);

            return $payment;
        });
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Paiement enregistré avec succès.', 'payment' => $payment, 'total_paid' => $payment->project->total_paid, 'remaining_amount' => $payment->project->remaining_amount], 201);
        }

        return redirect()->route('projects.show', $payment->project)->with('success', 'Paiement enregistré.');
    }

    public function destroy(Payment $payment, ActivityLogService $activity): RedirectResponse
    {
        DB::transaction(function () use ($payment, $activity): void {
            $project = $payment->project;
            $amount = $payment->amount;
            $payment->delete();
            $activity->record($project, 'payment_deleted', 'Paiement de '.Money::format($amount).' supprimé.');
        });

        return back()->with('success', 'Paiement supprimé.');
    }
}
