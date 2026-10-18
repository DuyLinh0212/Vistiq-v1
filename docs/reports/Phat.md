# Kiểm tra Phát — Content Approval / Attempts / Exam Runner

- Branch: `feature/phat-assessment-modules`; mốc đầu: `9eaba07e`.
- Căn cứ: module-ownership, ba SCOPE/design/proposal, contract builder và AGENTS.md.

## Các phần chưa đáp ứng tại mốc đầu

| Vấn đề | Bằng chứng / ảnh hưởng | Kết quả sửa |
| --- | --- | --- |
| Bỏ qua kiểm tra phiên bản khi chấm lại | `grade_token` là nullable cả ở request và service; bỏ token vẫn ghi đè điểm đã có. | Chấm lại bắt buộc token; thiếu trả 422, stale trả 409; giữ điểm và audit cũ khi bị từ chối. |
| Policy tắt đánh dấu chỉ được áp dụng ở UI | Gửi `flagged=true` vào answers vẫn thành công khi snapshot có `review_later=false`. | Kiểm tra snapshot phía server, trả 422 và không ghi batch đáp án. |
| Thay đổi ngoài scope và phụ thuộc Analytics | Branch sửa LeaderboardService/view, shared model/migration, asset/layout, AiService, dispatcher và fixture/test tích hợp. | Integration owner tiếp nhận review; thống nhất nguồn leaderboard/export sau merge với Viên. |

Commit sửa: `deaf752`. Test chấm lại hợp lệ được cập nhật để gửi token đúng của điểm đang xem; không bỏ kiểm tra xung đột để làm test đạt.

## Kiểm thử

- Trước sửa: **63/63 backend test đạt, 331 assertions**; JavaScript runner 2/2 đạt.
- Hai test mới đều tái hiện lỗi trước sửa.
- Sau sửa: **65/65 backend test đạt, 340 assertions**.
- Bao phủ queue/revision stale, self-approval, validation/reject, scoring/moderation, ownership, snapshot, retry/version, pause/timeout, audio/recording, lịch sử và privacy leaderboard.
- Pint, `git diff --check` và `graphify update .` hoàn tất.
- Test dùng SQLite RAM. MySQL và browser/reconnect/responsive được kiểm tra lại ở giai đoạn tích hợp; không đồng nhất test đơn vị JavaScript với E2E browser.

## Còn xử lý ở tích hợp

Export cần dùng cùng truy vấn điều kiện công bố/rank/time/ẩn danh, không xuất riêng trang đầu. Review layout/dialog dùng chung để giữ đủ các action của cả ba branch. Kết quả cuối ghi tại báo cáo tích hợp.
