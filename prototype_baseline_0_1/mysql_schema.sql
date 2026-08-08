-- MODELBASE-0.1 / PROTOBASE-0.1
-- Runtime schema only. Verification expected outcomes are intentionally absent.

CREATE TABLE prototype_baseline (
    prototype_baseline_id VARCHAR(32) PRIMARY KEY,
    model_baseline_id VARCHAR(32) NOT NULL,
    requirements_catalogue_version VARCHAR(16) NOT NULL,
    source_register_version VARCHAR(16) NOT NULL,
    domain_baseline_id VARCHAR(32) NOT NULL,
    rule_baseline_id VARCHAR(32) NOT NULL,
    case_baseline_id VARCHAR(32) NOT NULL,
    subset_baseline_id VARCHAR(32) NOT NULL,
    catalogue_edition VARCHAR(64) NOT NULL,
    diaglist_sha256 CHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE catalogue_code (
    subset_baseline_id VARCHAR(32) NOT NULL,
    code VARCHAR(10) NOT NULL,
    marker VARCHAR(4) NULL,
    designation VARCHAR(512) NOT NULL,
    short_designation VARCHAR(512) NOT NULL,
    PRIMARY KEY (subset_baseline_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE case_definition (
    case_baseline_id VARCHAR(32) NOT NULL,
    case_id VARCHAR(32) NOT NULL,
    subset_baseline_id VARCHAR(32) NOT NULL,
    short_description TEXT NOT NULL,
    encounter_setting VARCHAR(32) NOT NULL,
    diagnosis_role VARCHAR(16) NOT NULL,
    inpatient_lkf_scored BOOLEAN NULL,
    copd_base_code VARCHAR(10) NULL,
    fev1_stable_pct_predicted DECIMAL(6,2) NULL,
    intended_use VARCHAR(32) NOT NULL,
    source_locator VARCHAR(512) NOT NULL,
    PRIMARY KEY (case_baseline_id, case_id),
    UNIQUE KEY uq_case_subset (case_baseline_id, case_id, subset_baseline_id),
    CONSTRAINT ck_case_setting CHECK (encounter_setting IN ('inpatient', 'hospital_outpatient')),
    CONSTRAINT ck_case_role CHECK (diagnosis_role IN ('main', 'additional')),
    CONSTRAINT ck_case_use CHECK (intended_use IN ('learner_visible', 'verification_only')),
    CONSTRAINT ck_lkf_boolean CHECK (inpatient_lkf_scored IS NULL OR inpatient_lkf_scored IN (0, 1)),
    CONSTRAINT ck_outpatient_lkf_flag CHECK (
        (encounter_setting = 'inpatient' AND inpatient_lkf_scored IS NULL)
        OR
        (encounter_setting = 'hospital_outpatient' AND inpatient_lkf_scored IS NOT NULL)
    ),
    CONSTRAINT fk_case_copd_base
        FOREIGN KEY (subset_baseline_id, copd_base_code)
        REFERENCES catalogue_code (subset_baseline_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE case_code_domain (
    case_baseline_id VARCHAR(32) NOT NULL,
    case_id VARCHAR(32) NOT NULL,
    subset_baseline_id VARCHAR(32) NOT NULL,
    code VARCHAR(10) NOT NULL,
    is_acceptable BOOLEAN NOT NULL,
    PRIMARY KEY (case_baseline_id, case_id, subset_baseline_id, code),
    CONSTRAINT ck_acceptable_boolean CHECK (is_acceptable IN (0, 1)),
    CONSTRAINT fk_domain_case
        FOREIGN KEY (case_baseline_id, case_id, subset_baseline_id)
        REFERENCES case_definition (case_baseline_id, case_id, subset_baseline_id),
    CONSTRAINT fk_domain_code
        FOREIGN KEY (subset_baseline_id, code)
        REFERENCES catalogue_code (subset_baseline_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- There is deliberately no reference_response/expected_class table here.
-- RCBASE-0.2 is an external verification fixture and must not be a runtime
-- input to the classification endpoint.
