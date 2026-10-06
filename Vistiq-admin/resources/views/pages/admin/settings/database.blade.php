@if ($errors->any())
    <div class="admin-alert admin-alert-error">
        <b>Không thể hoàn tất thao tác.</b>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="admin-dashboard-grid">
    <section class="panel database-backup-panel">
        <span class="eyebrow">DATABASE · EXPORT</span>
        <h2>Sao lưu cơ sở dữ liệu</h2>
        <p>Chọn nội dung cần đưa vào file SQL. Có thể chọn cấu trúc, dữ liệu hoặc cả hai.</p>

        <form class="database-backup-form" method="post" action="{{ route('admin.settings.database.backup') }}">
            @csrf
            <label>Xác nhận mật khẩu hiện tại<input type="password" name="password" autocomplete="current-password" required></label>
            <label class="database-backup-option"><input type="hidden" name="include_schema" value="0"><input type="checkbox" name="include_schema" value="1" checked> <span>Cấu trúc bảng (schema)</span></label>
            <p class="form-help">Tạo lại cấu trúc bảng khi phục hồi. Không chọn mục này nếu chỉ muốn nạp dữ liệu vào các bảng hiện có.</p>

            <label class="database-backup-option"><input type="hidden" name="include_data" value="0"><input type="checkbox" name="include_data" value="1" checked> <span>Dữ liệu bảng</span></label>
            <p class="form-help">Bao gồm toàn bộ bản ghi hiện có trong cơ sở dữ liệu.</p>

            <button class="button button-primary" type="submit">@include('pages.dictionary.icon', ['name' => 'saved']) Tải file .sql</button>
        </form>
    </section>

    <section class="panel database-backup-panel">
        <span class="eyebrow">DATABASE · RESTORE</span>
        <h2>Phục hồi từ file SQL</h2>
        <p>Chỉ nhận file sao lưu được xuất từ trang này, tối đa 100 MB.</p>

        <div class="notice-box">
            Trong lúc phục hồi, Vistiq user sẽ tạm hiển thị trang bảo trì. Nếu file có schema, các bảng hiện tại sẽ được tạo lại; nếu chỉ có data, dữ liệu hiện có sẽ bị thay thế.
        </div>

        <form class="database-backup-form" method="post" action="{{ route('admin.settings.database.restore') }}" enctype="multipart/form-data" data-confirm="Phục hồi sẽ thay thế dữ liệu hiện có. Bạn đã chọn đúng file sao lưu chưa?">
            @csrf
            <label>Xác nhận mật khẩu hiện tại<input type="password" name="password" autocomplete="current-password" required></label>
            <label class="admin-label" for="database-backup-file">File sao lưu (.sql)
                <input class="admin-input" id="database-backup-file" type="file" name="file" accept=".sql,application/sql,text/plain" required>
            </label>
            <button class="button button-primary" type="submit">Phục hồi dữ liệu</button>
        </form>
    </section>
</div>
