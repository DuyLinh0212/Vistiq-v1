@php($revisionStatus = $revision?->status ?? 'draft')
@php($isSuperAdmin = auth()->user()?->hasRole('super-admin') ?? false)
<div class="assessment-actions"><span class="badge badge-soft">{{ strtoupper($revisionStatus) }}</span>
@foreach(['review'=>['content.edit','draft','Gửi duyệt'],'approve'=>['content.review','review','Duyệt'],'reject'=>['content.review','review','Từ chối'],'publish'=>['content.publish','approved','Xuất bản'],'archive'=>['content.publish','published','Archive']] as $action=>$definition)
@can($definition[0]) @if($revisionStatus===$definition[1] || ($action==='publish' && $isSuperAdmin && in_array($revisionStatus, ['draft', 'review'], true)))
<form method="post" action="{{ route('admin.content.transition',['kind'=>$kind,'id'=>$entityId,'action'=>$action]) }}">@csrf
@if($action==='reject')<input name="note" required placeholder="Lý do từ chối" aria-label="Lý do từ chối">@endif
<button class="button button-quiet">{{ $definition[2] }}</button></form>
@endif @endcan @endforeach
@can('content.edit')<form method="post" action="{{ route('admin.content.clone',['kind'=>$kind,'id'=>$entityId]) }}">@csrf<button class="button button-quiet">Tạo draft mới</button></form>@endcan
</div>
