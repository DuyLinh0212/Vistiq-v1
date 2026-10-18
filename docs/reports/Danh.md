# Kiểm tra Danh — Users & Permissions / Authentication

- Branch: `feature/users-permissions-authentication`; mốc đầu: `5ea757c`.
- Căn cứ: module-ownership, hai SCOPE/design/proposal, thiết kế auth và AGENTS.md.

## Các phần chưa đáp ứng tại mốc đầu

| Vấn đề | Bằng chứng / ảnh hưởng | Kết quả sửa |
| --- | --- | --- |
| Validation role vượt độ dài cột | Request nhận code 50, name 255, description 1.000; database chỉ nhận 40/100/255. Dữ liệu hợp lệ theo request có thể lỗi khi ghi MySQL. | Đồng bộ request và giới hạn trên form; dữ liệu quá dài trả 422. |
| Có thể gỡ quyền của role Admin mặc định | POST permissions rỗng vào role `admin` vẫn thành công. Trái quy định giữ Admin toàn quyền. | Bảo vệ phía request/service và hiển thị chỉ đọc cho cả Admin/Super Admin. |
| Có thể tự khóa tài khoản đang dùng | Khi còn Super Admin khác, POST khóa chính mình vẫn thành công. | Chặn bằng 409, giữ trạng thái active. |
| Chưa có E2E xuyên các module | Branch chỉ có unit/feature test và các ghi nhận preview. | Kiểm tra E2E liên module được thực hiện ở giai đoạn tích hợp. |

Commit sửa code: `c916a98`. Audit role update bổ sung số thành viên bị ảnh hưởng; xóa role khóa bản ghi trong transaction.

## Kiểm thử

- Trước sửa: 60/60 test thuộc hai module đạt, 328 assertions.
- Ba test hồi quy mới đều tái hiện lỗi trước sửa.
- Sau sửa: **63/63 đạt, 344 assertions**; gồm reset/session, malformed permissions, ma trận quyền rỗng, OTP/throttle và truy cập trực tiếp thiếu quyền.
- Pint và `git diff --check` đạt; chạy `graphify update .`.
- Dùng SQLite RAM, mail giả lập. Không đánh dấu giao SMTP/OTP tới hộp thư thật là đã kiểm chứng.
- Các lỗi trong báo cáo audit cũ về quyền rỗng, reset session và permissions sai kiểu đã được commit trước mốc đầu sửa; chạy lại đạt, không tính là lỗi còn tồn tại.

## Tích hợp

Asset auth và partial password dùng chung đã thay đổi ngoài scope module ở các commit gốc; integration owner tiếp nhận review và kiểm thử hồi quy. Kết quả browser/E2E cuối được ghi trong báo cáo tích hợp.
