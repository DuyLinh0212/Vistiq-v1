# Báo cáo tích hợp Vistiq

## Mốc và thứ tự merge

- Gốc `main`: `76793fc`; baseline 285 test, 274 đạt, 9 failure, 2 error.
- Danh: đầu `5ea757c`, sửa `c916a98`, báo cáo `0d2ee37`.
- Phát: đầu `9eaba07`, sửa `deaf752`, báo cáo `afe27b4`; merge `bce7116`.
- Viên: đầu `c04dba9`, sửa `6d06ab7`, báo cáo `6953402`; merge `9d034a2`.
- Thứ tự merge: Danh → Phát → Viên. Không squash, không force push hoặc xóa branch nguồn.
- Conflict chức năng nằm ở view leaderboard: giữ filter/export/reset của Viên, rank_position của Phát và dialog chung. Graph được hợp nhất node/relationship hai phía rồi dựng lại từ code.

## Các sửa tích hợp

- CSV/XLSX dùng cùng nguồn lọc; export cần cả results.view và results.export. Catalog/seeder giữ Admin/Super Admin toàn quyền; migration bổ sung key cho database đã triển khai, không cấp mặc định cho reviewer.
- Export leaderboard lấy toàn bộ trang, giới hạn 10.000 dòng và báo lỗi khi vượt giới hạn; giữ best attempt, đồng hạng, thời gian, privacy và mốc reset. XLSX được tạo bằng PhpSpreadsheet và đọc lại bằng test.
- Thống kê trung bình/độ khó dùng chung tập attempt, gồm lọc variant practice cũ, cancelled/is_test và các filter loại đề/kỹ năng/trạng thái. Không triển khai module Skill Practice.
- Layout Admin dùng biến điều hướng riêng, tránh ghi đè `$item` khiến editor Knowledge lỗi 500. Thêm preview bản nháp, phản hồi chung, pending export và bảo vệ submit lặp.
- Khôi phục GET nhóm câu hỏi và control import/template CSV; giữ cả workbook và CSV. Sửa workbook lookup part theo exam type + skill thay vì ghi đè part có code trùng. Chặn reading_key cùng đợt bị tách thành hai bài khác part.
- Chỉ sửa assertion THPT để xác nhận alias thpt-english trỏ blueprint 2025 hiện hành. Fixture TOEIC Part 1 bổ sung ảnh bắt buộc và fake Cloudinary; không bỏ validation ảnh để làm test đạt.
- Migration cũ trên branch Viên chỉ có thay đổi format, không có thay đổi schema cần backfill. Migration current_item_index của Phát được giữ.

## Kiểm chứng trước phần Khoa

- Sau merge, trước sửa tích hợp: 404/413 đạt; 9 failure giống baseline, hai error AiService đã được sửa ở branch Phát.
- Sau sửa: **416/416 đạt, 2.206 assertions**, SQLite RAM.
- `npm run build` đạt. Có cảnh báo fontaine tùy chọn và kích thước bundle Admin; không coi warning là build failure.
- Test và log chi tiết cục bộ ở `storage/logs/integration/` (không commit dữ liệu kiểm thử).

## Kiểm chứng cuối

Phần Khoa, MySQL cô lập, E2E browser và responsive sẽ được bổ sung sau khi triển khai và chạy thực tế.
