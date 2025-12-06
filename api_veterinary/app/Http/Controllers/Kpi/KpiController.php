<?php

namespace App\Http\Controllers\Kpi;

use Carbon\Carbon;
use App\Models\Pets\Pet;
use Illuminate\Http\Request;
use App\Models\MedicalRecord;
use App\Models\Surgerie\Surgerie;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Vaccination\Vaccination;
use App\Models\Surgerie\SurgeriePayment;
use App\Models\Appointment\AppointmentPayment;
use App\Models\Vaccination\VaccinationPayment;

class KpiController extends Controller
{
    /**
     * Helper: calcula variación porcentual cuidando división por cero
     * Retorna float
     */
    private function calcVariation(float $current, float $before): float
    {
        if ($before == 0.0) {
            if ($current == 0.0) return 0.0;
            return 100.0; // decisión: si before = 0 y current > 0 => 100% (puedes ajustar si prefieres null)
        }
        return (($current - $before) / $before) * 100.0;
    }

    /**
     * Reporte general (n mascotas, n servicios, ingresos netos, variación)
     */
    public function kpi_report_general(Request $request)
    {
        // Normalizar timezone; se recomienda poner este timezone en config/app.php también
        Carbon::setLocale('es');

        // Recibir year/month del request o usar now() en zona America/Lima
        $now = Carbon::now('America/Lima');
        $year = (int) ($request->input('year') ?? $now->format('Y'));
        $month = (int) ($request->input('month') ?? $now->format('m')); // 1..12

        // Conteos / sumas por mes
        $n_pets = Pet::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $net_income_appointments = (float) AppointmentPayment::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');

        $net_income_surgerie = (float) SurgeriePayment::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');

        $net_income_vaccination = (float) VaccinationPayment::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');

        $net_income_total_current = $net_income_appointments + $net_income_surgerie + $net_income_vaccination;

        // Mes anterior
        $month_before = Carbon::createFromDate($year, $month, 1, 'America/Lima')->subMonth();
        $yb = (int) $month_before->format('Y');
        $mb = (int) $month_before->format('m');

        $net_income_appointments_before = (float) AppointmentPayment::whereYear('created_at', $yb)
            ->whereMonth('created_at', $mb)
            ->sum('amount');

        $net_income_surgeries_before = (float) SurgeriePayment::whereYear('created_at', $yb)
            ->whereMonth('created_at', $mb)
            ->sum('amount');

        $net_income_vaccination_before = (float) VaccinationPayment::whereYear('created_at', $yb)
            ->whereMonth('created_at', $mb)
            ->sum('amount');

        $net_income_total_before = $net_income_appointments_before + $net_income_surgeries_before + $net_income_vaccination_before;

        // Variación con protección
        $variation_percentage = $this->calcVariation($net_income_total_current, $net_income_total_before);

        // N° de servicios del mes actual (medical_records)
        $n_service_record = MedicalRecord::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'month_name' => Carbon::createFromDate($year, $month, 1, 'America/Lima')->monthName,
            'n_pets' => (int) $n_pets,
            'n_service_record' => (int) $n_service_record,
            'variation_percentage' => round($variation_percentage, 2),
            'net_income_total_before' => (float) $net_income_total_before,
            'net_income_total' => (float) $net_income_total_current,
        ]);
    }

    /**
     * Veterinario con mayor ingreso neto
     */
    public function kpi_veterinarie_net_income(Request $request)
    {
        Carbon::setLocale('es');
        $now = Carbon::now('America/Lima');
        $year = (int) ($request->input('year') ?? $now->format('Y'));
        $month = (int) ($request->input('month') ?? $now->format('m'));

        $veterinaries_net_income = collect([]);

        // CITAS
        $appointments_veterinaries = DB::table('appointment_payments')
            ->whereNull('appointment_payments.deleted_at')
            ->join('appointments', 'appointments.id', '=', 'appointment_payments.appointment_id')
            ->join('users', 'users.id', '=', 'appointments.veterinarie_id')
            ->whereNull('appointments.deleted_at')
            ->whereYear('appointment_payments.created_at', $year)
            ->whereMonth('appointment_payments.created_at', $month)
            ->selectRaw("appointments.veterinarie_id,(users.name || ' ' || users.surname) as full_name,users.gender, CAST(SUM(appointment_payments.amount) AS DOUBLE PRECISION) as net_income, count(*) as count_payments")
            ->groupBy('appointments.veterinarie_id', 'full_name', 'users.gender')
            ->orderBy('net_income', 'desc')
            ->first();

        if ($appointments_veterinaries) $veterinaries_net_income->push($appointments_veterinaries);

        // VACUNAS
        $vaccination_veterinaries = DB::table('vaccination_payments')
            ->whereNull('vaccination_payments.deleted_at')
            ->join('vaccinations', 'vaccinations.id', '=', 'vaccination_payments.vaccination_id')
            ->join('users', 'users.id', '=', 'vaccinations.veterinarie_id')
            ->whereNull('vaccinations.deleted_at')
            ->whereYear('vaccination_payments.created_at', $year)
            ->whereMonth('vaccination_payments.created_at', $month)
            ->selectRaw("vaccinations.veterinarie_id,(users.name || ' ' || users.surname) as full_name,users.gender, CAST(SUM(vaccination_payments.amount) AS DOUBLE PRECISION) as net_income, count(*) as count_payments")
            ->groupBy('vaccinations.veterinarie_id', 'full_name', 'users.gender')
            ->orderBy('net_income', 'desc')
            ->first();

        if ($vaccination_veterinaries) $veterinaries_net_income->push($vaccination_veterinaries);

        // CIRUGÍAS
        $surgerie_veterinaries = DB::table('surgerie_payments')
            ->whereNull('surgerie_payments.deleted_at')
            ->join('surgeries', 'surgeries.id', '=', 'surgerie_payments.surgerie_id')
            ->join('users', 'users.id', '=', 'surgeries.veterinarie_id')
            ->whereNull('surgeries.deleted_at')
            ->whereYear('surgerie_payments.created_at', $year)
            ->whereMonth('surgerie_payments.created_at', $month)
            ->selectRaw("surgeries.veterinarie_id,(users.name || ' ' || users.surname) as full_name,users.gender, CAST(SUM(surgerie_payments.amount) AS DOUBLE PRECISION) as net_income, count(*) as count_payments")
            ->groupBy('surgeries.veterinarie_id', 'full_name', 'users.gender')
            ->orderBy('net_income', 'desc')
            ->first();

        if ($surgerie_veterinaries) $veterinaries_net_income->push($surgerie_veterinaries);

        // Agrupar por veterinarie_id y sumar net_income
        $veterinaries_group = collect([]);
        foreach ($veterinaries_net_income->groupBy('veterinarie_id') as $key => $veterinaries) {
            $veterinaries_group->push([
                'veterinarie_id' => $key,
                'full_name' => $veterinaries[0]->full_name ?? null,
                'gender' => $veterinaries[0]->gender ?? null,
                'net_income_total' => $veterinaries->sum('net_income'),
            ]);
        }

        $veterinarie_most_net_income = $veterinaries_group->isNotEmpty() ? $veterinaries_group->sortByDesc('net_income_total')->first() : null;

        $veterinarie_id = $veterinarie_most_net_income['veterinarie_id'] ?? null;

        $variation_percentage = 0.0;
        $net_income_total_before = 0.0;

        if ($veterinarie_id) {
            $month_before = Carbon::createFromDate($year, $month, 1, 'America/Lima')->subMonth();
            $yb = (int) $month_before->format('Y');
            $mb = (int) $month_before->format('m');

            $net_income_appointments_before = (float) AppointmentPayment::whereYear('created_at', $yb)
                ->whereMonth('created_at', $mb)
                ->whereHas('appointment', function ($q) use ($veterinarie_id) {
                    $q->where('veterinarie_id', $veterinarie_id);
                })
                ->sum('amount');

            $net_income_surgeries_before = (float) SurgeriePayment::whereYear('created_at', $yb)
                ->whereMonth('created_at', $mb)
                ->whereHas('surgerie', function ($q) use ($veterinarie_id) {
                    $q->where('veterinarie_id', $veterinarie_id);
                })
                ->sum('amount');

            $net_income_vaccination_before = (float) VaccinationPayment::whereYear('created_at', $yb)
                ->whereMonth('created_at', $mb)
                ->whereHas('vaccination', function ($q) use ($veterinarie_id) {
                    $q->where('veterinarie_id', $veterinarie_id);
                })
                ->sum('amount');

            $net_income_total_before = $net_income_appointments_before + $net_income_surgeries_before + $net_income_vaccination_before;

            $variation_percentage = $this->calcVariation($veterinarie_most_net_income['net_income_total'], $net_income_total_before);
        }

        return response()->json([
            'year' => $year,
            'month_name' => Carbon::createFromDate($year, $month, 1, 'America/Lima')->monthName,
            'variation_percentage' => round((float) $variation_percentage, 2),
            'net_income_total_before' => (float) $net_income_total_before,
            'veterinarie_most_net_income' => $veterinarie_most_net_income,
        ]);
    }

    /**
     * Veterinario con más servicios asignados
     */
    public function kpi_veterinarie_most_asigned(Request $request)
    {
        $now = Carbon::now('America/Lima');
        $year = (int) ($request->input('year') ?? $now->format('Y'));
        $month = (int) ($request->input('month') ?? $now->format('m'));

        $veterinarie_most_asigned = DB::table('medical_records')
            ->whereNull('medical_records.deleted_at')
            ->join('users', 'medical_records.veterinarie_id', '=', 'users.id')
            ->whereYear('medical_records.created_at', $year)
            ->whereMonth('medical_records.created_at', $month)
            ->selectRaw("(users.name || ' ' || users.surname )as full_name,users.gender, medical_records.veterinarie_id,count(*) as n_asigned")
            ->groupBy('veterinarie_id', 'full_name', 'users.gender')
            ->orderBy('n_asigned', 'desc')
            ->first();

        $month_before = Carbon::createFromDate($year, $month, 1, 'America/Lima')->subMonth();
        $n_most_asigned_before = 0;
        $variation_percentage = 0.0;

        if ($veterinarie_most_asigned) {
            $n_most_asigned_before = DB::table('medical_records')
                ->whereNull('medical_records.deleted_at')
                ->join('users', 'medical_records.veterinarie_id', '=', 'users.id')
                ->whereYear('medical_records.created_at', $month_before->format('Y'))
                ->whereMonth('medical_records.created_at', $month_before->format('m'))
                ->where('medical_records.veterinarie_id', $veterinarie_most_asigned->veterinarie_id)
                ->count();

            $variation_percentage = $this->calcVariation((float) $veterinarie_most_asigned->n_asigned, (float) $n_most_asigned_before);
        }

        return response()->json([
            'variation_percentage' => round($variation_percentage, 2),
            'n_most_asigned_before' => (int) $n_most_asigned_before,
            'veterinarie_most_asigned' => $veterinarie_most_asigned,
        ]);
    }

    /**
     * KPI total bruto (pagos vs deuda)
     */
    public function kpi_total_bruto(Request $request)
    {
        $now = Carbon::now('America/Lima');
        $year = (int) ($request->input('year') ?? $now->format('Y'));
        $month = (int) ($request->input('month') ?? $now->format('m'));

        // SUMAS BRUTAS
        $total_bruto_appointments = (float) Appointment::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
        $total_bruto_vaccinations = (float) Vaccination::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
        $total_bruto_surgeries = (float) Surgerie::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');

        $total_bruto_general = $total_bruto_appointments + $total_bruto_vaccinations + $total_bruto_surgeries;

        // INGRESOS NETOS (pagos)
        $net_income_appointments = (float) AppointmentPayment::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
        $net_income_surgerie = (float) SurgeriePayment::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
        $net_income_vaccination = (float) VaccinationPayment::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');

        $net_income_total_current = $net_income_appointments + $net_income_surgerie + $net_income_vaccination;

        // Evitar división por cero al calcular %pagado/deuda
        $percentage_payments = ($total_bruto_general == 0.0) ? 0.0 : ($net_income_total_current / $total_bruto_general) * 100.0;
        $total_not_payments = $total_bruto_general - $net_income_total_current;
        $percentage_not_payments = ($total_bruto_general == 0.0) ? 0.0 : ($total_not_payments / $total_bruto_general) * 100.0;

        $month_before = Carbon::createFromDate($year, $month, 1, 'America/Lima')->subMonth();
        $yb = (int) $month_before->format('Y');
        $mb = (int) $month_before->format('m');

        $total_bruto_appointments_before = (float) Appointment::whereYear('created_at', $yb)->whereMonth('created_at', $mb)->sum('amount');
        $total_bruto_vaccinations_before = (float) Vaccination::whereYear('created_at', $yb)->whereMonth('created_at', $mb)->sum('amount');
        $total_bruto_surgeries_before = (float) Surgerie::whereYear('created_at', $yb)->whereMonth('created_at', $mb)->sum('amount');

        $total_bruto_general_before = $total_bruto_appointments_before + $total_bruto_vaccinations_before + $total_bruto_surgeries_before;

        $variation_percentage = $this->calcVariation($total_bruto_general, $total_bruto_general_before);

        return response()->json([
            'variation_percentage' => round($variation_percentage, 2),
            'total_bruto_general_before' => (float) $total_bruto_general_before,
            'percentage_not_payments' => round($percentage_not_payments, 2),
            'total_not_payments' => (float) $total_not_payments,
            'percentage_payments' => round($percentage_payments, 2),
            'total_payments' => (float) $net_income_total_current,
            'total_bruto_general' => (float) $total_bruto_general,
            'total_bruto_surgeries' => (float) $total_bruto_surgeries,
            'total_bruto_vaccinations' => (float) $total_bruto_vaccinations,
            'total_bruto_appointments' => (float) $total_bruto_appointments,
        ]);
    }

    /**
     * Reporte por servicios (citas, vacunas, cirugías)
     */
    public function kpi_report_for_servicies(Request $request)
    {
        $now = Carbon::now('America/Lima');
        $year = (int) ($request->input('year') ?? $now->format('Y'));
        $month = (int) ($request->input('month') ?? $now->format('m'));

        $month_before = Carbon::createFromDate($year, $month, 1, 'America/Lima')->subMonth();
        $yb = (int) $month_before->format('Y');
        $mb = (int) $month_before->format('m');

        // CITAS
        $bruto_income_appoinments = (float) Appointment::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
        $bruto_income_appoinments_before = (float) Appointment::whereYear('created_at', $yb)->whereMonth('created_at', $mb)->sum('amount');
        $VPAppointments = $this->calcVariation($bruto_income_appoinments, $bruto_income_appoinments_before);

        $n_appointments_total = (int) Appointment::whereYear('created_at', $year)->whereMonth('created_at', $month)->count();
        $n_appointments_attend = (int) Appointment::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 3)->count();
        $n_appointments_cancel = (int) Appointment::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 2)->count();
        $n_appointments_pending = (int) Appointment::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 1)->count();

        // VACUNAS
        $bruto_income_vaccinations = (float) Vaccination::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
        $bruto_income_vaccinations_before = (float) Vaccination::whereYear('created_at', $yb)->whereMonth('created_at', $mb)->sum('amount');
        $VPVaccinations = $this->calcVariation($bruto_income_vaccinations, $bruto_income_vaccinations_before);

        $n_vaccinations_total = (int) Vaccination::whereYear('created_at', $year)->whereMonth('created_at', $month)->count();
        $n_vaccinations_attend = (int) Vaccination::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 3)->count();
        $n_vaccinations_cancel = (int) Vaccination::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 2)->count();
        $n_vaccinations_pending = (int) Vaccination::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 1)->count();

        // CIRUGÍAS
        $bruto_income_surgeries = (float) Surgerie::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
        $bruto_income_surgeries_before = (float) Surgerie::whereYear('created_at', $yb)->whereMonth('created_at', $mb)->sum('amount');
        $VPsurgeries = $this->calcVariation($bruto_income_surgeries, $bruto_income_surgeries_before);

        $n_surgeries_total = (int) Surgerie::whereYear('created_at', $year)->whereMonth('created_at', $month)->count();
        $n_surgeries_attend = (int) Surgerie::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 3)->count();
        $n_surgeries_cancel = (int) Surgerie::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 2)->count();
        $n_surgeries_pending = (int) Surgerie::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('state', 1)->count();

        return response()->json([
            'n_surgeries_total' => $n_surgeries_total,
            'n_surgeries_attend' => $n_surgeries_attend,
            'n_surgeries_cancel' => $n_surgeries_cancel,
            'n_surgeries_pending' => $n_surgeries_pending,
            'VPsurgeries' => round($VPsurgeries, 2),
            'bruto_income_surgeries' => (float) $bruto_income_surgeries,
            'bruto_income_surgeries_before' => (float) $bruto_income_surgeries_before,
            'n_vaccinations_total' => $n_vaccinations_total,
            'n_vaccinations_attend' => $n_vaccinations_attend,
            'n_vaccinations_cancel' => $n_vaccinations_cancel,
            'n_vaccinations_pending' => $n_vaccinations_pending,
            'VPVaccinations' => round($VPVaccinations, 2),
            'bruto_income_vaccinations_before' => (float) $bruto_income_vaccinations_before,
            'bruto_income_vaccinations' => (float) $bruto_income_vaccinations,
            'n_appointments_pending' => $n_appointments_pending,
            'n_appointments_cancel' => $n_appointments_cancel,
            'n_appointments_attend' => $n_appointments_attend,
            'n_appointments_total' => $n_appointments_total,
            'VPAppointments' => round($VPAppointments, 2),
            'bruto_income_appoinments' => (float) $bruto_income_appoinments,
            'bruto_income_appoinments_before' => (float) $bruto_income_appoinments_before,
        ]);
    }

    /**
     * Mascota con más pagos
     */
    public function kpi_pets_most_payments(Request $request)
    {
        $now = Carbon::now('America/Lima');
        $year = (int) ($request->input('year') ?? $now->format('Y'));
        $month = (int) ($request->input('month') ?? $now->format('m'));

        $month_before = Carbon::createFromDate($year, $month, 1, 'America/Lima')->subMonth();
        $yb = (int) $month_before->format('Y');
        $mb = (int) $month_before->format('m');

        // inicializar variables por seguridad
        $payments_appointment_before = 0.0;
        $payments_vaccination_before = 0.0;
        $payments_surgerie_before = 0.0;

        $pet_payments = collect([]);

        $appointments_pet_payment = DB::table('appointment_payments')
            ->whereNull('appointment_payments.deleted_at')
            ->join('appointments', 'appointment_payments.appointment_id', '=', 'appointments.id')
            ->join('pets', 'appointments.pet_id', '=', 'pets.id')
            ->whereNull('appointments.deleted_at')
            ->whereYear('appointment_payments.created_at', $year)
            ->whereMonth('appointment_payments.created_at', $month)
            ->selectRaw("appointments.pet_id,pets.name, CAST(SUM(appointment_payments.amount) AS DOUBLE PRECISION) as payment_total, count(*) as count_payments")
            ->groupBy('appointments.pet_id', 'pets.name')
            ->orderBy('payment_total', 'desc')
            ->first();

        if ($appointments_pet_payment) $pet_payments->push($appointments_pet_payment);

        $vaccination_pet_payment = DB::table('vaccination_payments')
            ->whereNull('vaccination_payments.deleted_at')
            ->join('vaccinations', 'vaccination_payments.vaccination_id', '=', 'vaccinations.id')
            ->join('pets', 'vaccinations.pet_id', '=', 'pets.id')
            ->whereNull('vaccinations.deleted_at')
            ->whereYear('vaccination_payments.created_at', $year)
            ->whereMonth('vaccination_payments.created_at', $month)
            ->selectRaw("vaccinations.pet_id,pets.name, CAST(SUM(vaccination_payments.amount) AS DOUBLE PRECISION) as payment_total, count(*) as count_payments")
            ->groupBy('vaccinations.pet_id', 'pets.name')
            ->orderBy('payment_total', 'desc')
            ->first();

        if ($vaccination_pet_payment) $pet_payments->push($vaccination_pet_payment);

        $surgerie_pet_payment = DB::table('surgerie_payments')
            ->whereNull('surgerie_payments.deleted_at')
            ->join('surgeries', 'surgerie_payments.surgerie_id', '=', 'surgeries.id')
            ->join('pets', 'surgeries.pet_id', '=', 'pets.id')
            ->whereNull('surgeries.deleted_at')
            ->whereYear('surgerie_payments.created_at', $year)
            ->whereMonth('surgerie_payments.created_at', $month)
            ->selectRaw("surgeries.pet_id,pets.name, CAST(SUM(surgerie_payments.amount) AS DOUBLE PRECISION) as payment_total, count(*) as count_payments")
            ->groupBy('surgeries.pet_id', 'pets.name')
            ->orderBy('payment_total', 'desc')
            ->first();

        if ($surgerie_pet_payment) $pet_payments->push($surgerie_pet_payment);

        $pets_groups = collect([]);
        foreach ($pet_payments->groupBy('pet_id') as $key => $pet) {
            $pets_groups->push([
                'pet_id' => $key,
                'name' => $pet[0]->name ?? null,
                'payment_totals' => $pet->sum('payment_total'),
            ]);
        }

        $pet_most_payments = $pets_groups->isNotEmpty() ? $pets_groups->sortByDesc('payment_totals')->first() : null;
        $VPPets = 0.0;
        $payments_total_before = 0.0;

        if ($pet_most_payments) {
            $pet_id = $pet_most_payments['pet_id'];

            $payments_appointment_before = (float) AppointmentPayment::whereYear('created_at', $yb)
                ->whereMonth('created_at', $mb)
                ->whereHas('appointment', function ($q) use ($pet_id) {
                    $q->where('pet_id', $pet_id);
                })->sum('amount');

            $payments_vaccination_before = (float) VaccinationPayment::whereYear('created_at', $yb)
                ->whereMonth('created_at', $mb)
                ->whereHas('vaccination', function ($q) use ($pet_id) {
                    $q->where('pet_id', $pet_id);
                })->sum('amount');

            $payments_surgerie_before = (float) SurgeriePayment::whereYear('created_at', $yb)
                ->whereMonth('created_at', $mb)
                ->whereHas('surgerie', function ($q) use ($pet_id) {
                    $q->where('pet_id', $pet_id);
                })->sum('amount');

            $payments_total_before = $payments_appointment_before + $payments_vaccination_before + $payments_surgerie_before;

            $VPPets = $this->calcVariation((float) $pet_most_payments['payment_totals'], (float) $payments_total_before);
        }

        return response()->json([
            'VPPets' => round($VPPets, 2),
            'payments_total_before' => (float) $payments_total_before,
            'payments_vaccination_before' => (float) $payments_vaccination_before,
            'payments_surgerie_before' => (float) $payments_surgerie_before,
            'payments_appointment_before' => (float) $payments_appointment_before,
            'pet_most_payments' => $pet_most_payments,
            'pets_groups' => $pets_groups,
            'surgerie_pet_payment' => $surgerie_pet_payment,
            'vaccination_pet_payment' => $vaccination_pet_payment,
            'appointments_pet_payment' => $appointments_pet_payment,
        ]);
    }

    /**
     * Pagos por día del mes (recibe year y month)
     */
    public function kpi_payments_x_day_month(Request $request)
    {
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        if (!$year || !$month) {
            return response()->json(['error' => 'year and month are required'], 422);
        }

        $payments_for_day_generals = collect([]);

        $appointment_payments_for_day_month = DB::table('appointment_payments')->whereNull('appointment_payments.deleted_at')
            ->whereYear('appointment_payments.created_at', $year)
            ->whereMonth('appointment_payments.created_at', $month)
            ->selectRaw("
                TO_CHAR(appointment_payments.created_at, 'YYYY-MM-DD') as created_at_format,
                TO_CHAR(appointment_payments.created_at, 'MM-DD') AS day_created_format,
                CAST(SUM(appointment_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format', 'day_created_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($appointment_payments_for_day_month as $appointment_payment) {
            $payments_for_day_generals->push($appointment_payment);
        }

        $vaccinations_payments_for_day_month = DB::table('vaccination_payments')->whereNull('vaccination_payments.deleted_at')
            ->whereYear('vaccination_payments.created_at', $year)
            ->whereMonth('vaccination_payments.created_at', $month)
            ->selectRaw("
                TO_CHAR(vaccination_payments.created_at, 'YYYY-MM-DD') as created_at_format,
                TO_CHAR(vaccination_payments.created_at, 'MM-DD') AS day_created_format,
                CAST(SUM(vaccination_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format', 'day_created_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($vaccinations_payments_for_day_month as $vaccination_payment) {
            $payments_for_day_generals->push($vaccination_payment);
        }

        $surgeries_payments_for_day_month = DB::table('surgerie_payments')->whereNull('surgerie_payments.deleted_at')
            ->whereYear('surgerie_payments.created_at', $year)
            ->whereMonth('surgerie_payments.created_at', $month)
            ->selectRaw("
                TO_CHAR(surgerie_payments.created_at, 'YYYY-MM-DD') as created_at_format,
                TO_CHAR(surgerie_payments.created_at, 'MM-DD') AS day_created_format,
                CAST(SUM(surgerie_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format', 'day_created_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($surgeries_payments_for_day_month as $surgeries_payment) {
            $payments_for_day_generals->push($surgeries_payment);
        }

        $payment_for_day_months = collect([]);
        foreach ($payments_for_day_generals->groupBy('created_at_format') as $key => $payment_generals) {
            $payment_for_day_months->push([
                'created_at_format' => $key,
                'day_created_format' => $payment_generals[0]->day_created_format,
                'total_payments' => $payment_generals->sum('total_payments'),
            ]);
        }

        return response()->json([
            'total_payments_month' => (float) $payment_for_day_months->sum('total_payments'),
            'payment_for_day_months' => $payment_for_day_months->sortBy('created_at_format')->values()->all(),
            'payments_for_day_generals' => $payments_for_day_generals,
            'surgeries_payments_for_day_month' => $surgeries_payments_for_day_month,
            'vaccinations_payments_for_day_month' => $vaccinations_payments_for_day_month,
            'appointment_payments_for_day_month' => $appointment_payments_for_day_month,
        ]);
    }

    /**
     * Pagos por mes del año (recibe year)
     */
    public function kpi_payments_x_month_of_year(Request $request)
    {
        $year = (int) $request->input('year');

        if (!$year) {
            return response()->json(['error' => 'year is required'], 422);
        }

        $payments_x_month = collect([]);

        $appointment_x_month_of_year = DB::table('appointment_payments')->whereNull('appointment_payments.deleted_at')
            ->whereYear('appointment_payments.created_at', $year)
            ->selectRaw("
                TO_CHAR(appointment_payments.created_at, 'YYYY-MM') as created_at_format,
                CAST(SUM(appointment_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($appointment_x_month_of_year as $appointment_x_month) {
            $payments_x_month->push($appointment_x_month);
        }

        $vaccination_x_month_of_year = DB::table('vaccination_payments')->whereNull('vaccination_payments.deleted_at')
            ->whereYear('vaccination_payments.created_at', $year)
            ->selectRaw("
                TO_CHAR(vaccination_payments.created_at, 'YYYY-MM') as created_at_format,
                CAST(SUM(vaccination_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($vaccination_x_month_of_year as $vaccination_x_month) {
            $payments_x_month->push($vaccination_x_month);
        }

        $surgerie_x_month_of_year = DB::table('surgerie_payments')->whereNull('surgerie_payments.deleted_at')
            ->whereYear('surgerie_payments.created_at', $year)
            ->selectRaw("
                TO_CHAR(surgerie_payments.created_at, 'YYYY-MM') as created_at_format,
                CAST(SUM(surgerie_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($surgerie_x_month_of_year as $surgerie_x_month) {
            $payments_x_month->push($surgerie_x_month);
        }

        $payment_x_month_of_year = collect([]);
        foreach ($payments_x_month->groupBy('created_at_format') as $key => $payments) {
            $payment_x_month_of_year->push([
                'created_at_format' => $key,
                'total_payments' => $payments->sum('total_payments'),
            ]);
        }

        // Año anterior
        $payments_x_month_before = collect([]);

        $appointment_x_month_of_year_before = DB::table('appointment_payments')->whereNull('appointment_payments.deleted_at')
            ->whereYear('appointment_payments.created_at', $year - 1)
            ->selectRaw("
                TO_CHAR(appointment_payments.created_at, 'YYYY-MM') as created_at_format,
                CAST(SUM(appointment_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($appointment_x_month_of_year_before as $appointment_x_month) {
            $payments_x_month_before->push($appointment_x_month);
        }

        $vaccination_x_month_of_year_before = DB::table('vaccination_payments')->whereNull('vaccination_payments.deleted_at')
            ->whereYear('vaccination_payments.created_at', $year - 1)
            ->selectRaw("
                TO_CHAR(vaccination_payments.created_at, 'YYYY-MM') as created_at_format,
                CAST(SUM(vaccination_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($vaccination_x_month_of_year_before as $vaccination_x_month) {
            $payments_x_month_before->push($vaccination_x_month);
        }

        $surgerie_x_month_of_year_before = DB::table('surgerie_payments')->whereNull('surgerie_payments.deleted_at')
            ->whereYear('surgerie_payments.created_at', $year - 1)
            ->selectRaw("
                TO_CHAR(surgerie_payments.created_at, 'YYYY-MM') as created_at_format,
                CAST(SUM(surgerie_payments.amount) AS DOUBLE PRECISION) as total_payments
            ")
            ->groupBy('created_at_format')
            ->orderBy('created_at_format', 'asc')
            ->get();

        foreach ($surgerie_x_month_of_year_before as $surgerie_x_month) {
            $payments_x_month_before->push($surgerie_x_month);
        }

        $payment_x_month_of_year_before = collect([]);
        foreach ($payments_x_month_before->groupBy('created_at_format') as $key => $payments) {
            $payment_x_month_of_year_before->push([
                'created_at_format' => $key,
                'total_payments' => $payments->sum('total_payments'),
            ]);
        }

        return response()->json([
            'total_payment_before' => (float) $payment_x_month_of_year_before->sum('total_payments'),
            'total_payment_current' => (float) $payment_x_month_of_year->sum('total_payments'),
            'payment_x_month_of_year_before' => $payment_x_month_of_year_before,
            'payment_x_month_of_year' => $payment_x_month_of_year,
            'payments_x_month' => $payments_x_month,
            'surgerie_x_month_of_year' => $surgerie_x_month_of_year,
            'vaccination_x_month_of_year' => $vaccination_x_month_of_year,
            'appointment_x_month_of_year' => $appointment_x_month_of_year,
        ]);
    }
}
