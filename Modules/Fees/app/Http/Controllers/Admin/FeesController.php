<?php

namespace Modules\Fees\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeStructure;

class FeesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Fees', [
            'heads' => FeeHead::orderBy('ordering')->get(),
            'structures' => FeeStructure::with(['session:id,code', 'program:id,code,name', 'items.head:id,code,name'])
                ->orderByDesc('id')
                ->get(),
            'sessions' => AcademicSession::orderByDesc('is_active')->orderByDesc('commencement_date')->get(['id', 'code', 'name', 'is_active']),
            'programs' => Program::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function storeHead(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:fee_heads,code'],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'in:application,tuition,other'],
            'is_refundable' => ['sometimes', 'boolean'],
            'ordering' => ['nullable', 'integer'],
        ]);

        FeeHead::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Fee head created.']);
    }

    public function updateHead(Request $request, FeeHead $head): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'in:application,tuition,other'],
            'is_refundable' => ['sometimes', 'boolean'],
            'ordering' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $head->update($data);

        return back()->with('flash', ['success' => 'Fee head updated.']);
    }

    public function storeStructure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'reservation_category_id' => ['nullable', 'exists:reservation_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fee_head_id' => ['required', 'exists:fee_heads,id'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum('amount');

            $structure = FeeStructure::create([
                'academic_session_id' => $data['academic_session_id'],
                'program_id' => $data['program_id'],
                'reservation_category_id' => $data['reservation_category_id'] ?? null,
                'name' => $data['name'],
                'total_amount' => $total,
                'status' => 'draft',
            ]);

            foreach ($data['items'] as $i => $item) {
                $structure->items()->create([
                    'fee_head_id' => $item['fee_head_id'],
                    'amount' => $item['amount'],
                    'ordering' => $i,
                ]);
            }
        });

        return back()->with('flash', ['success' => 'Fee structure created.']);
    }
}
