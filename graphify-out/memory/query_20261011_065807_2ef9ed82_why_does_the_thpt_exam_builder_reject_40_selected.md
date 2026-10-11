---
type: "query"
date: "2026-10-11T06:58:07.960072+00:00"
question: "Why does the THPT exam builder reject 40 selected questions?"
contributor: "graphify"
outcome: "useful"
source_nodes: ["ExamGenerationService", ".validateSelection()", "exam-builder.js", "ExamBuilderController.php"]
---

# Q: Why does the THPT exam builder reject 40 selected questions?

## Answer

Expanded from original query via graph vocab: [exam, builder, group, question, selected, count, difficulty, available, selection, validation, part, passage]. The exam builder displays draft questions and counts them in the selected total, while ExamGenerationService::pool only includes published questions and validateSelection compares each part against that eligible pool and the configured difficulty quotas. The current Part 2 question group has six draft questions and no published revision, so the generic validation error is expected even though the UI total is 40.

## Outcome

- Signal: useful

## Source Nodes

- ExamGenerationService
- .validateSelection()
- exam-builder.js
- ExamBuilderController.php