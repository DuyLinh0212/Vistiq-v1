# Vistiq database schema

## MySQL connection

Laravel reads connection settings from `.env` through `config/database.php`. The MySQL and MariaDB connections use `utf8mb4` and InnoDB so foreign keys and transactions work consistently. `.env.example` is configured for a Laragon MySQL database named `vistiq`; local credentials stay in `.env`.

The local Laragon MySQL database `vistiq` has been created and all migrations have been applied. The checked-in `schema.sql` is a structure-only export of that database; `data.sql` contains the imported English–Vietnamese dictionary data.

The dictionary import contains 104,829 headwords, 175,735 meanings, 175,735 English examples, 1,502 explicitly tagged idioms, and 1,945 inferred phrasal-verb entries. The source provides no Vietnamese example translations, so those columns are `NULL`. Since this schema stores one IPA per headword, the import keeps a preferred pronunciation (British variants first where available). The source does not mark phrasal verbs as a separate type; those rows are inferred from verb phrases and a matching base headword.

The dictionary is derived from Skypedia's `english-vietnamese-dictionary`, based on MinhQND Dictionary, and is licensed under CC BY-SA 4.0. Preserve the source `ATTRIBUTION.md` if redistributing this derived dataset.

## Table count

The migrations define **63 application and Laravel runtime tables**. Laravel also creates its migration-history table, `migrations`, so a freshly migrated database contains **64 physical tables**:

- **55 Vistiq tables** for accounts, dictionary and grammar content, exam authoring, attempts, grading, knowledge saves, notifications, and achievements.
- **8 Laravel infrastructure tables** for users, password resets, sessions, cache, and queues.
- **1 Laravel migration-history table:** `migrations`.

The framework tables are `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs`.

### Vistiq tables by feature

- **Accounts (4):** `roles`, `role_user`, `user_profiles`, `user_preferences`.
- **Learning content and saved knowledge (20):** `proficiency_levels`, `parts_of_speech`, `learning_item_types`, `grammar_categories`, `tags`, `learning_items`, `dictionary_entries`, `learning_item_meanings`, `learning_item_meaning_examples`, `collocations`, `collocation_dictionary_entries`, `phrasal_verbs`, `idioms`, `learning_item_relations`, `grammar_topics`, `grammar_rules`, `grammar_examples`, `learning_item_tags`, `user_saved_items`, `saved_item_reviews`.
- **Exam authoring and question bank (16):** `exam_types`, `skills`, `practice_modes`, `question_types`, `question_difficulties`, `exam_parts`, `learning_stimuli`, `question_bank_items`, `question_options`, `question_accepted_answers`, `score_scales`, `score_scale_ranges`, `exam_templates`, `exam_sections`, `exam_section_questions`, `exam_policies`.
- **Attempts, grading, and moderation (12):** `attempt_event_types`, `attempt_report_reasons`, `grading_rubrics`, `grading_criteria`, `exam_attempts`, `attempt_answers`, `attempt_answer_options`, `attempt_events`, `attempt_results`, `question_rubrics`, `answer_grades`, `attempt_reports`.
- **Notifications and achievements (3):** `user_notifications`, `achievement_definitions`, `user_achievements`.

## 3NF design

- Fixed classifications such as proficiency levels, parts of speech, exam types, question types, and skills have lookup tables.
- Dictionary, collocation, phrasal verb, idiom, grammar, and question content share `learning_items`; type-specific fields live in one-to-one detail tables.
- Meanings and examples use one-to-many tables. Exam choices, accepted text answers, and multi-select responses are stored as rows rather than JSON arrays.
- Many-to-many relations use explicit pivot tables. A user's saved item points to one learning item and is unique per user.
- Attempt totals are isolated in `attempt_results`; scaled scores and bands are resolved through the exam's score scale and its ranges. Leaderboards are derived from approved attempts instead of copied into a separate ranking table.
- Attempt events, human grading criteria, grades, and moderation reports are stored separately from the exam definition and answer rows.

## Migrations

| Migration | Tables |
| --- | --- |
| `2026_10_02_121057_create_user_access_tables` | roles, role_user, user_profiles, user_preferences |
| `2026_10_02_121058_create_learning_content_tables` | 20 learning and knowledge tables |
| `2026_10_02_121059_create_assessment_catalog_tables` | 16 exam and question-bank tables |
| `2026_10_02_121100_create_attempt_review_and_grading_tables` | 12 attempt, grading, and report tables |
| `2026_10_02_121101_create_user_engagement_tables` | user_notifications, achievement_definitions, user_achievements |

The three original Laravel migrations create the 8 infrastructure tables listed above.
