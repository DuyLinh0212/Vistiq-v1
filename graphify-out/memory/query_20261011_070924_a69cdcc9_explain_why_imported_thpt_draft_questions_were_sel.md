---
type: "query"
date: "2026-10-11T07:09:24.163974+00:00"
question: "Explain why imported THPT draft questions were selectable but rejected in generated exam templates, and distinguish that from publishing a manual draft exam."
contributor: "graphify"
outcome: "useful"
source_nodes: ["ExamGenerationService", ".validateSelection()", "ExamPublishingService", "ExamBuilderController.php"]
---

# Q: Explain why imported THPT draft questions were selectable but rejected in generated exam templates, and distinguish that from publishing a manual draft exam.

## Answer

The exam catalog displayed draft questions and let them be selected, while ExamGenerationService.validateSelection validates template selections against a published-only pool. The manual exam screenshot and database record were still draft; ExamPublishingService.publish requires exam approval and published question revisions. Updated the catalog metadata and builder UI to disable questions without published content for generated templates, explain why, and retain the manual draft workflow.

## Outcome

- Signal: useful

## Source Nodes

- ExamGenerationService
- .validateSelection()
- ExamPublishingService
- ExamBuilderController.php