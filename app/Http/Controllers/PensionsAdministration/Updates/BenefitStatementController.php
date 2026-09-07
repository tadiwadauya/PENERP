<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Member;
use App\Services\PensionsAdministration\BenefitStatements\BenefitStatementService;
use Illuminate\Http\Request;
use Throwable;

class BenefitStatementController extends Controller
{
    public function index(Request $request)
    {
        $query=trim((string)$request->input('search'));
        $statementDate=$request->input('statement_date');

        $members=collect();

        if ($query!=='') {
            $members=Member::query()
                ->where(function($q) use($query): void {
                    $q->where('member_number','like',"%{$query}%")
                        ->orWhere('penad_member_number','like',"%{$query}%")
                        ->orWhere('fundworx_member_number','like',"%{$query}%")
                        ->orWhere('national_id','like',"%{$query}%")
                        ->orWhere('surname','like',"%{$query}%")
                        ->orWhere('first_names','like',"%{$query}%");
                })
                ->orderBy('surname')
                ->orderBy('first_names')
                ->limit(50)
                ->get();
        }

        return view('pensions-administration.updates.benefit-statements.index',compact('members','query','statementDate'));
    }

    public function preview(Request $request,BenefitStatementService $service)
    {
        $validated=$request->validate([
            'member_id'=>['required','integer','exists:members,id'],
            'statement_date'=>['required','date','before_or_equal:today'],
        ]);

        try {
            $member=Member::query()->findOrFail($validated['member_id']);
            $statement=$service->generate($member,$validated['statement_date']);

            return view('pensions-administration.updates.benefit-statements.preview',compact('member','statement'));
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('pensions-administration.updates.benefit-statements.index',[
                    'search'=>$request->input('search'),
                    'statement_date'=>$validated['statement_date'],
                ])
                ->withInput()
                ->with('error',$e->getMessage());
        }
    }
}