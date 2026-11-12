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
            <button class="btn btn-success bulk-submit" type="submit" name="action" value="download">Download All Statements</button>
            <button class="btn btn-primary bulk-submit" type="submit" name="action" value="print">Print All Statements</button>
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
<div class="row mt-3"><div class="col-lg-9"><div class="card border-0 shadow-sm"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Recent Exports</h6><button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">Refresh Runs</button></div>
    <p class="small text-muted">Regeneration uses the same employer and statement date, but creates a new PDF and retains the previous export.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Employer</th><th>Statement Date</th><th>Generated On</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>@foreach($recentRuns as $run)
        <tr>
            <td>{{ $run->employer_name }}</td>
            <td>{{ \Carbon\Carbon::parse($run->statement_date)->format('d M Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($run->created_at)->format('d M Y H:i') }}</td>
            <td><span class="badge bg-{{ $run->status==='completed'?'success':($run->status==='failed'?'danger':($run->status==='cancelled'?'warning':'secondary')) }}">{{ ucfirst(str_replace('_',' ',$run->status)) }}</span></td>
            <td><div class="d-flex flex-wrap gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="watchRun(@js($run->id),@js($run->requested_action ?? 'download'),false)">View progress</button>
                @if(in_array($run->status,['queued','preparing','processing','merging']))
                    <button type="button" class="btn btn-sm btn-outline-danger cancel-btn" data-run-id="{{ $run->id }}" data-employer="{{ $run->employer_name }}" onclick="cancelRun(this)">Cancel</button>
                @endif
                @if(in_array($run->status,['completed','failed','cancelled']))
                    <button type="button" class="btn btn-sm btn-outline-success regenerate-btn" data-run-id="{{ $run->id }}" data-employer="{{ $run->employer_name }}" data-date="{{ $run->statement_date }}" data-action="{{ $run->requested_action }}">Regenerate</button>
                @endif
            </div></td>
        </tr>
        @endforeach</tbody>
    </table></div>
</div></div></div></div>
@endif
<script>
let bulkTimer=null;
let currentAction='download';
let submitting=false;
let pollSequence=0;
const bulkForm=document.getElementById('bulk-form');
const progress=document.getElementById('bulk-progress');
const statusText=document.getElementById('bulk-status');
const countText=document.getElementById('bulk-count');
const bar=document.getElementById('bulk-bar');
const result=document.getElementById('bulk-result');
const startUrl=bulkForm.getAttribute('action');
const statusTemplate=@json(route('pensions-administration.updates.benefit-statements.bulk.status',['id'=>'__RUN_ID__']));
const regenerateTemplate=@json(route('pensions-administration.updates.benefit-statements.bulk.regenerate',['id'=>'__RUN_ID__']));
const cancelTemplate=@json(route('pensions-administration.updates.benefit-statements.bulk.cancel',['id'=>'__RUN_ID__']));
const csrfToken=bulkForm.querySelector('[name="_token"]').value;
function urlFor(template,id) { return template.replace('__RUN_ID__',encodeURIComponent(String(id))); }
function stopPolling() { pollSequence++; if(bulkTimer) clearTimeout(bulkTimer); bulkTimer=null; }
function setSubmitting(value) {
    submitting=value;
    document.querySelectorAll('.bulk-submit,.regenerate-btn,.cancel-btn').forEach(b=>b.disabled=value);
}
function openProgress() {
    progress.style.display='block';
    progress.hidden=false;
    progress.scrollIntoView({behavior:'smooth',block:'center'});
}
function resetProgress() {
    stopPolling();openProgress();
    statusText.textContent='Submitting request…';
    countText.textContent='Checking eligible members…';
    bar.style.width='0%';bar.textContent='0%';bar.setAttribute('aria-valuenow','0');
    bar.classList.remove('bg-danger','bg-success');bar.classList.add('progress-bar-animated');
    result.replaceChildren();result.className='mt-2';
}
async function jsonResponse(response) {
    const type=response.headers.get('content-type')||'';
    if(!type.includes('application/json')) throw new Error('Unexpected server response ('+response.status+').');
    const data=await response.json();
    if(!response.ok) throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Request failed ('+response.status+').');
    return data;
}
function addLink(label,href,css,newTab=false) {
    if(!href) return null;
    const a=document.createElement('a');a.href=href;a.className=css;a.textContent=label;
    if(newTab) { a.target='_blank';a.rel='noopener'; }
    result.appendChild(a);return a;
}
function watchRun(id,action='download',autoDownload=false) {
    stopPolling();const sequence=pollSequence;
    currentAction=action==='print'?'print':'download';
    openProgress();result.replaceChildren();result.className='mt-2';
    statusText.textContent='Retrieving run status…';countText.textContent='Please wait…';
    bar.classList.remove('bg-danger','bg-success');bar.classList.add('progress-bar-animated');
    let errors=0;
    const poll=async()=>{
        if(sequence!==pollSequence) return;
        try {
            const response=await fetch(urlFor(statusTemplate,id),{headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store'});
            const data=await jsonResponse(response);
            if(sequence!==pollSequence) return;
            errors=0;
            const state=String(data.status||'unknown');
            const total=Number(data.total)||0, processed=Number(data.processed)||0, failed=Number(data.failed)||0;
            const percent=Math.max(0,Math.min(100,Number(data.percent)||0));
            statusText.textContent=(data.employer||'Employer')+' — '+state.replaceAll('_',' ');
            bar.style.width=percent+'%';bar.textContent=percent+'%';bar.setAttribute('aria-valuenow',percent);
            countText.textContent=total?`${processed.toLocaleString()} of ${total.toLocaleString()} members processed (${failed} failed)`:'Preparing eligible members…';
            if(state==='completed') {
                stopPolling();bar.classList.remove('progress-bar-animated');bar.classList.add('bg-success');
                bar.style.width='100%';bar.textContent='100%';bar.setAttribute('aria-valuenow','100');
                const download=addLink('Download PDF',data.download_url,'btn btn-success me-2 mb-2');
                addLink('Print PDF',data.print_url,'btn btn-primary mb-2',true);
                if(autoDownload&&currentAction==='download'&&download) download.click();
                if(autoDownload&&currentAction==='print') result.insertAdjacentText('beforeend',' Click Print PDF to print.');
                return;
            }
            if(state==='failed'||state==='cancelled') {
                stopPolling();bar.classList.remove('progress-bar-animated');
                if(state==='failed') bar.classList.add('bg-danger');
                result.textContent=state==='cancelled'?'This generation was cancelled.':('Generation failed: '+(data.message||'Check Laravel logs.'));
                return;
            }
            bulkTimer=setTimeout(poll,2000);
        } catch(error) {
            if(sequence!==pollSequence) return;
            countText.textContent=error.message+' Retrying…';
            if(++errors>=10) { stopPolling();result.textContent='Progress could not be retrieved. Refresh Runs and try View Progress again.';return; }
            bulkTimer=setTimeout(poll,3000);
        }
    };
    poll();
}
async function submitGeneration(url,formData,action) {
    if(submitting) return;
    setSubmitting(true);resetProgress();currentAction=action;
    try {
        const response=await fetch(url,{method:'POST',body:formData,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
        const data=await jsonResponse(response);
        if(!data.run_id) throw new Error('No run ID returned by the server.');
        watchRun(data.run_id,action,true);
    } catch(error) {
        statusText.textContent='Unable to start';result.textContent=error.message;result.className='text-danger mt-2';
    } finally { setSubmitting(false); }
}
bulkForm.addEventListener('submit',event=>{
    event.preventDefault();if(submitting||!bulkForm.reportValidity()) return;
    const action=event.submitter?.value==='print'?'print':'download';
    const formData=new FormData(bulkForm);formData.set('action',action);
    submitGeneration(startUrl,formData,action);
});
function regenerateRun(button) {
    if(submitting) return;
    const id=button.dataset.runId;
    if(!confirm('Regenerate Benefit Statements for '+button.dataset.employer+' as at '+button.dataset.date+'? A new run will be created and the previous export retained.')) return;
    const data=new FormData();data.set('_token',csrfToken);data.set('action','download');
    submitGeneration(urlFor(regenerateTemplate,id),data,'download');
}
async function cancelRun(button) {
    if(submitting) return;
    const id=button.dataset.runId;
    if(!confirm('Cancel the current Benefit Statement generation for '+button.dataset.employer+'? Completed work in this run will not be downloadable as a complete PDF.')) return;
    setSubmitting(true);
    try {
        const response=await fetch(urlFor(cancelTemplate,id),{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrfToken},credentials:'same-origin'});
        await jsonResponse(response);
        watchRun(id,'download',false);
        button.closest('tr').querySelector('td:nth-child(4) .badge').textContent='Cancelled';
        button.remove();
    } catch(error) { alert('Unable to cancel: '+error.message); }
    finally { setSubmitting(false); }
}
document.querySelectorAll('.regenerate-btn').forEach(button=>button.addEventListener('click',()=>regenerateRun(button)));
</script>
@endsection
