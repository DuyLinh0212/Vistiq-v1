---
type: "query"
date: "2026-10-11T06:45:55.845534+00:00"
question: "Fix THPT English Excel import error for missing category, question type, and difficulty initialization"
contributor: "graphify"
outcome: "useful"
source_nodes: ["Admin/AssessmentCatalogSeeder.php", "AssessmentCatalogSeeder", "AdminCatalogSeeder", "QuestionController.php"]
---

# Q: Fix THPT English Excel import error for missing category, question type, and difficulty initialization

## Answer

Expanded from original query via graph vocab: [import, question, assessment, catalog, difficulty, section, seed, type, validation, admin, bank, valid]. The graph links the THPT assessment catalog seeder with the question controller import path. Source inspection and a read-only preview confirmed this database lacked the thpt exam type and tieng-anh-part-1 through tieng-anh-part-6 exam parts; after the idempotent AssessmentCatalogSeeder ran, preview accepted all 40 questions across six sheets without errors.

## Outcome

- Signal: useful

## Source Nodes

- Admin/AssessmentCatalogSeeder.php
- AssessmentCatalogSeeder
- AdminCatalogSeeder
- QuestionController.php