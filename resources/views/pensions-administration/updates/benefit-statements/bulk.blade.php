@extends('layouts.app')

@section('title','Bulk Benefit Statements')
@section('page-heading','Bulk Benefit Statements')
@section('page-subheading','Generate A3 benefit statements by employer with live progress')

@section('content')
@include('pensions-administration.partials.navigation')
<div class="row"><div class="col-lg-9"><div class="card border-0 shadow-sm"><div class="card-body">
    <h5>Employer Benefit Statements</h5>
    <p class="text-muted">Select an employer and statement date. The system processes active members with expected contributions during the selected year up to that date. Each member has two A3 landscape pages.</p>
    <form id="bulk-form" action="{{ route('pensions-administration.updates.benefit-statements.bulk.start') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-7"><label class="form-label" for="employer_id">Employer</label><select class="form-select" name="employer_id" id="employer_id" required><option value="">Select employer</option>@foreach($employers as $employer)<option value="{{ $employer->id }}">{{ $employer->name }}</option>@endforeach</select></div>
            <div class="col-md-5"><label class="form-label" for="statement_date">Statement Date</label><input class="form-control" type="date" name="statement_date" id="statement_date" value="{{ now()->subYear()->endOfYear()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-success" type="submit" name="action" value="download">Download All Statements</button>
            <button class="btn btn-primary" type="submit" name="action" value="print">Print All Statements</button>
            <a class="btn btn-light" href="{{ route('pensions-administration.updates.benefit-statements.index') }}">Individual Statements</a>
        </div>
    </form>
    <div id="bulk-progress" class="mt-4" style="display:none" role="status" aria-live="polite">
        <strong id="bulk-status">Preparing…</strong>
        <div class="progress mt-2" style="height:24px"><div id="bulk-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div></div>
        <p class="small text-muted mt-2" id="bulk-count">Checking eligible members…</p>
        <div id="bulk-result"></div>
    </div>
</div></div></div></div>
@if($recentRuns->isNotEmpty())
<div class="row mt-3"><div class="col-lg-9"><div class="card border-0 shadow-sm"><div class="card-body"><h6>Recent Exports</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Employer</th><th>Date</th><th>Status</th><th>Open</th></tr></thead><tbody>@foreach($recentRuns as $run)<tr><td>{{ $run->employer_name }}</td><td>{{ $run->statement_date }}</td><td>{{ $run->status }}</td><td><button type="button" class="btn btn-sm btn-outline-primary" onclick="watchRun('{{ $run->id }}','{{ $run->requested_action }}')">View progress</button></td></tr>@endforeach</tbody></table></div></div></div></div></div>
@endif
<script>
let bulkTimer=null;
let currentAction='download';
const bulkForm=document.getElementById('bulk-form');
const progress=document.getElementById('bulk-progress');
const statusText=document.getElementById('bulk-status');
const countText=document.getElementById('bulk-count');
const bar=document.getElementById('bulk-bar');
const result=document.getElementById('bulk-result');
const statusTemplate=@json(route('pensions-administration.updates.benefit-statements.bulk.status',['id'=>'__RUN_ID__']));
function watchRun(id,action='download') {
    currentAction=action;
    if(bulkTimer) clearInterval(bulkTimer);
    progress.style.display='block'; result.innerHTML='';
    const poll=async()=>{
        try {
            const response=await fetch(statusTemplate.replace('__RUN_ID__',id),{headers:{'Accept':'application/json'}});
            if(!response.ok) throw new Error('Unable to retrieve progress ('+response.status+').');
            const data=await response.json();
            statusText.textContent=data.employer+' — '+data.status.replaceAll('_',' ');
            const percent=Math.max(0,Math.min(100,data.percent));
            bar.style.width=percent+'%';bar.textContent=percent+'%';bar.setAttribute('aria-valuenow',percent);
            countText.textContent=data.total?`${data.processed.toLocaleString()} of ${data.total.toLocaleString()} members processed`:'Checking eligible members…';
            if(data.status==='completed') {
                clearInterval(bulkTimer);bulkTimer=null;
                bar.classList.remove('progress-bar-animated');bar.style.width='100%';bar.textContent='100%';
                result.innerHTML='<a class="btn btn-success me-2" id="bulk-download" href="'+data.download_url+'">Download PDF</a><a class="btn btn-primary" id="bulk-print" target="_blank" rel="noopener" href="'+data.print_url+'">Print PDF</a>';
                if(currentAction==='download') document.getElementById('bulk-download').click();
                // For print, click the ready Print PDF link. Browsers may block popups from asynchronous polling.
            } else if(data.status==='failed') {
                clearInterval(bulkTimer);bulkTimer=null;
                result.textContent='Generation failed: '+(data.message||'Check Laravel logs and failed queue jobs.');
                result.className='text-danger';
            }
        } catch(error) { countText.textContent=error.message+' Retrying…'; }
    };
    poll();bulkTimer=setInterval(poll,2000);
}
bulkForm.addEventListener('submit',async(event)=>{
    event.preventDefault();
    if(!bulkForm.reportValidity()) return;
    currentAction=event.submitter?.value||'download';
    const data=new FormData(bulkForm);data.set('action',currentAction);
    progress.style.display='block';statusText.textContent='Submitting request…';result.textContent='';
    try {
        const response=await fetch(bulkForm.getAttribute('action'),{method:'POST',body:data,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const payload=await response.json();
        if(!response.ok) throw new Error(payload.message||JSON.stringify(payload.errors||{}));
        watchRun(payload.run_id,currentAction);
    } catch(error) { statusText.textContent='Unable to start';result.textContent=error.message;result.className='text-danger'; }
});
</script>
@endsection
