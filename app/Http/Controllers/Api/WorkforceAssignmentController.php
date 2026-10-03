<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workforce;
use App\Models\WorkforceAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkforceAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkforceAssignment::with('workforce');

        if ($request->has('month') && $request->has('year')) {
            $month = (int)$request->month;
            $year = (int)$request->year;
            $query->whereYear('date', $year)->whereMonth('date', $month);
        } elseif ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->orderBy('date', 'asc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'workforce_ids' => 'required|array|min:1',
            'workforce_ids.*' => 'exists:workforces,id',
            'dates' => 'required|array|min:1',
            'dates.*' => 'date',
            'daily_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $assignments = DB::transaction(function () use ($request) {
            $created = [];
            foreach ($request->workforce_ids as $wId) {
                foreach ($request->dates as $dateStr) {
                    $assignment = WorkforceAssignment::updateOrCreate(
                        [
                            'workforce_id' => $wId,
                            'date' => $dateStr,
                        ],
                        [
                            'daily_rate' => $request->daily_rate,
                            'notes' => $request->notes,
                        ]
                    );
                    $created[] = $assignment;
                }
            }
            return $created;
        });

        return response()->json([
            'message' => 'Workforce rates assigned successfully',
            'assignments' => $assignments,
        ], 201);
    }

    public function destroy(WorkforceAssignment $workforceAssignment)
    {
        $workforceAssignment->delete();
        return response()->json(['message' => 'Assignment deleted successfully']);
    }

    public function monthlySummary(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000|max:2100',
            'fortnight' => 'nullable|string|in:all,1st,2nd',
        ]);

        $month = (int)$request->month;
        $year = (int)$request->year;
        $fortnight = $request->input('fortnight', 'all');

        $workers = Workforce::where('active', true)->orderBy('name')->get();

        $summary = $workers->map(function ($worker) use ($month, $year, $fortnight) {
            $query = WorkforceAssignment::where('workforce_id', $worker->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month);

            if ($fortnight === '1st') {
                $query->whereRaw('DAY(date) <= 15');
            } elseif ($fortnight === '2nd') {
                $query->whereRaw('DAY(date) >= 16');
            }

            $assignments = $query->get();

            $workedDays = $assignments->count();
            $totalAmount = (float)$assignments->sum('daily_rate');

            return [
                'workforce_id' => $worker->id,
                'worker_name' => $worker->name,
                'identifier' => $worker->identifier,
                'worked_days' => $workedDays,
                'total_amount' => round($totalAmount, 2),
                'assignments' => $assignments,
            ];
        });

        $grandTotal = round($summary->sum('total_amount'), 2);

        return response()->json([
            'year' => $year,
            'month' => $month,
            'fortnight' => $fortnight,
            'workers' => $summary,
            'grand_total' => $grandTotal,
        ]);
    }
}
