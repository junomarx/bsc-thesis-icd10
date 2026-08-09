-- MODELBASE-0.2 / PROTOBASE-1.0 schema, reused unchanged by the PROTOBASE-1.1 localization correction
-- Runtime schema only. RCBASE-0.3 and all expected verification outcomes are absent.

CREATE TABLE prototype_baseline (
    prototype_baseline_id VARCHAR(32) PRIMARY KEY,
    model_baseline_id VARCHAR(32) NOT NULL,
    requirements_catalogue_version VARCHAR(32) NOT NULL,
    source_register_version VARCHAR(16) NOT NULL,
    domain_baseline_id VARCHAR(32) NOT NULL,
    rule_baseline_id VARCHAR(32) NOT NULL,
    subset_baseline_id VARCHAR(32) NOT NULL,
    patient_baseline_id VARCHAR(32) NOT NULL,
    question_baseline_id VARCHAR(32) NOT NULL,
    catalogue_edition VARCHAR(64) NOT NULL,
    diaglist_sha256 CHAR(64) NOT NULL,
    CONSTRAINT ck_diaglist_sha256 CHECK (CHAR_LENGTH(diaglist_sha256) = 64)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE catalogue_code (
    subset_baseline_id VARCHAR(32) NOT NULL,
    code VARCHAR(10) NOT NULL,
    marker VARCHAR(4) NULL,
    designation VARCHAR(512) NOT NULL,
    short_designation VARCHAR(512) NOT NULL,
    PRIMARY KEY (subset_baseline_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_definition (
    patient_baseline_id VARCHAR(32) NOT NULL,
    patient_id VARCHAR(32) NOT NULL,
    display_name VARCHAR(128) NOT NULL,
    age_years SMALLINT UNSIGNED NOT NULL,
    sex VARCHAR(32) NOT NULL,
    self_described_background VARCHAR(256) NOT NULL,
    history_availability VARCHAR(64) NOT NULL,
    difficulty_role VARCHAR(32) NOT NULL,
    general_health_summary TEXT NOT NULL,
    synthetic BOOLEAN NOT NULL,
    PRIMARY KEY (patient_baseline_id, patient_id),
    CONSTRAINT ck_patient_age CHECK (age_years > 0 AND age_years <= 125),
    CONSTRAINT ck_patient_history CHECK (history_availability IN ('established', 'partial', 'unavailable_from_patient')),
    CONSTRAINT ck_patient_difficulty CHECK (difficulty_role IN ('foundational', 'involved')),
    CONSTRAINT ck_patient_synthetic CHECK (synthetic IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_context_item (
    patient_baseline_id VARCHAR(32) NOT NULL,
    patient_id VARCHAR(32) NOT NULL,
    context_item_id VARCHAR(32) NOT NULL,
    item_type VARCHAR(64) NOT NULL,
    information_source VARCHAR(64) NOT NULL,
    display_text TEXT NOT NULL,
    canonical_position SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (patient_baseline_id, patient_id, context_item_id),
    UNIQUE KEY uq_patient_context_position (patient_baseline_id, patient_id, canonical_position),
    CONSTRAINT ck_patient_context_position CHECK (canonical_position > 0),
    CONSTRAINT ck_patient_context_type CHECK (item_type IN (
        'documented_condition',
        'self_reported_history',
        'current_exam_finding',
        'social_context',
        'information_boundary',
        'other'
    )),
    CONSTRAINT fk_context_patient
        FOREIGN KEY (patient_baseline_id, patient_id)
        REFERENCES patient_definition (patient_baseline_id, patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coding_question (
    question_baseline_id VARCHAR(32) NOT NULL,
    question_id VARCHAR(32) NOT NULL,
    patient_baseline_id VARCHAR(32) NULL,
    patient_id VARCHAR(32) NULL,
    title VARCHAR(256) NOT NULL,
    prompt TEXT NOT NULL,
    intended_use VARCHAR(32) NOT NULL,
    canonical_position SMALLINT UNSIGNED NOT NULL,
    legacy_case_id VARCHAR(32) NULL,
    source_audit_ref VARCHAR(512) NOT NULL,
    PRIMARY KEY (question_baseline_id, question_id),
    UNIQUE KEY uq_patient_question_position (question_baseline_id, patient_baseline_id, patient_id, canonical_position),
    KEY ix_question_patient (patient_baseline_id, patient_id),
    CONSTRAINT ck_question_use CHECK (intended_use IN ('learner_visible', 'verification_only')),
    CONSTRAINT ck_question_position CHECK (canonical_position > 0),
    CONSTRAINT ck_question_patient_pair CHECK (
        (patient_baseline_id IS NULL AND patient_id IS NULL)
        OR (patient_baseline_id IS NOT NULL AND patient_id IS NOT NULL)
    ),
    CONSTRAINT ck_learner_question_patient CHECK (
        intended_use <> 'learner_visible'
        OR (patient_baseline_id IS NOT NULL AND patient_id IS NOT NULL)
    ),
    CONSTRAINT fk_question_patient
        FOREIGN KEY (patient_baseline_id, patient_id)
        REFERENCES patient_definition (patient_baseline_id, patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_fact (
    question_baseline_id VARCHAR(32) NOT NULL,
    question_id VARCHAR(32) NOT NULL,
    fact_key VARCHAR(64) NOT NULL,
    value_type VARCHAR(16) NOT NULL,
    value_text TEXT NULL,
    value_integer BIGINT NULL,
    value_decimal DECIMAL(16,4) NULL,
    value_boolean BOOLEAN NULL,
    value_code VARCHAR(32) NULL,
    value_enum VARCHAR(128) NULL,
    unit VARCHAR(64) NULL,
    learner_label VARCHAR(256) NOT NULL,
    source_context_item_id VARCHAR(32) NULL,
    PRIMARY KEY (question_baseline_id, question_id, fact_key),
    CONSTRAINT ck_fact_type CHECK (value_type IN ('text', 'integer', 'decimal', 'boolean', 'code', 'enum')),
    CONSTRAINT ck_fact_boolean CHECK (value_boolean IS NULL OR value_boolean IN (0, 1)),
    CONSTRAINT ck_fact_one_typed_value CHECK (
        (value_type = 'text' AND value_text IS NOT NULL AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_code IS NULL AND value_enum IS NULL)
        OR (value_type = 'integer' AND value_text IS NULL AND value_integer IS NOT NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_code IS NULL AND value_enum IS NULL)
        OR (value_type = 'decimal' AND value_text IS NULL AND value_integer IS NULL AND value_decimal IS NOT NULL AND value_boolean IS NULL AND value_code IS NULL AND value_enum IS NULL)
        OR (value_type = 'boolean' AND value_text IS NULL AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NOT NULL AND value_code IS NULL AND value_enum IS NULL)
        OR (value_type = 'code' AND value_text IS NULL AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_code IS NOT NULL AND value_enum IS NULL)
        OR (value_type = 'enum' AND value_text IS NULL AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_code IS NULL AND value_enum IS NOT NULL)
    ),
    CONSTRAINT fk_fact_question
        FOREIGN KEY (question_baseline_id, question_id)
        REFERENCES coding_question (question_baseline_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_code_domain (
    question_baseline_id VARCHAR(32) NOT NULL,
    question_id VARCHAR(32) NOT NULL,
    subset_baseline_id VARCHAR(32) NOT NULL,
    code VARCHAR(10) NOT NULL,
    relation_kind VARCHAR(40) NOT NULL,
    reason_key VARCHAR(96) NULL,
    improvement_code VARCHAR(10) NULL,
    source_audit_ref VARCHAR(512) NOT NULL,
    PRIMARY KEY (question_baseline_id, question_id, subset_baseline_id, code),
    CONSTRAINT ck_relation_kind CHECK (relation_kind IN (
        'accepted_reference',
        'less_specific_supported',
        'fact_conflict',
        'temporal_context_conflict',
        'source_rule_resolved'
    )),
    CONSTRAINT ck_relation_reason CHECK (
        relation_kind NOT IN ('fact_conflict', 'temporal_context_conflict')
        OR (reason_key IS NOT NULL AND reason_key <> '')
    ),
    CONSTRAINT ck_relation_improvement CHECK (
        relation_kind <> 'less_specific_supported'
        OR improvement_code IS NOT NULL
    ),
    CONSTRAINT fk_domain_question
        FOREIGN KEY (question_baseline_id, question_id)
        REFERENCES coding_question (question_baseline_id, question_id),
    CONSTRAINT fk_domain_code
        FOREIGN KEY (subset_baseline_id, code)
        REFERENCES catalogue_code (subset_baseline_id, code),
    CONSTRAINT fk_domain_improvement
        FOREIGN KEY (subset_baseline_id, improvement_code)
        REFERENCES catalogue_code (subset_baseline_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_relation_fact (
    question_baseline_id VARCHAR(32) NOT NULL,
    question_id VARCHAR(32) NOT NULL,
    subset_baseline_id VARCHAR(32) NOT NULL,
    code VARCHAR(10) NOT NULL,
    fact_key VARCHAR(64) NOT NULL,
    relation_role VARCHAR(40) NOT NULL,
    PRIMARY KEY (question_baseline_id, question_id, subset_baseline_id, code, fact_key),
    CONSTRAINT ck_relation_role CHECK (relation_role IN (
        'supports_reference',
        'conflicts_with_response',
        'supports_specificity',
        'supports_temporal_context',
        'supports_source_rule'
    )),
    CONSTRAINT fk_relation_fact_domain
        FOREIGN KEY (question_baseline_id, question_id, subset_baseline_id, code)
        REFERENCES question_code_domain (question_baseline_id, question_id, subset_baseline_id, code),
    CONSTRAINT fk_relation_fact_fact
        FOREIGN KEY (question_baseline_id, question_id, fact_key)
        REFERENCES question_fact (question_baseline_id, question_id, fact_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_option (
    question_baseline_id VARCHAR(32) NOT NULL,
    question_id VARCHAR(32) NOT NULL,
    option_id VARCHAR(48) NOT NULL,
    option_kind VARCHAR(24) NOT NULL,
    subset_baseline_id VARCHAR(32) NULL,
    code VARCHAR(10) NULL,
    canonical_position SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (question_baseline_id, question_id, option_id),
    UNIQUE KEY uq_question_option_position (question_baseline_id, question_id, canonical_position),
    CONSTRAINT ck_option_kind CHECK (option_kind IN ('code', 'none_of_above')),
    CONSTRAINT ck_option_position CHECK (canonical_position > 0),
    CONSTRAINT ck_option_payload CHECK (
        (option_kind = 'code' AND subset_baseline_id IS NOT NULL AND code IS NOT NULL)
        OR (option_kind = 'none_of_above' AND subset_baseline_id IS NULL AND code IS NULL)
    ),
    CONSTRAINT fk_option_question
        FOREIGN KEY (question_baseline_id, question_id)
        REFERENCES coding_question (question_baseline_id, question_id),
    CONSTRAINT fk_option_code
        FOREIGN KEY (subset_baseline_id, code)
        REFERENCES catalogue_code (subset_baseline_id, code),
    CONSTRAINT fk_option_domain
        FOREIGN KEY (question_baseline_id, question_id, subset_baseline_id, code)
        REFERENCES question_code_domain (question_baseline_id, question_id, subset_baseline_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deliberately absent: reference_response, expected_class, expected_rule,
-- RCBASE metadata, learner attempt/history tables, and any clinical inference table.
