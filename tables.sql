-- ============================================================
--  HR SYSTEM — MySQL / MariaDB
--  Modules: Employee Info, Onboarding, Timekeeping, Leave,
--           Payroll, Benefits, Self-Service, Reporting
-- ============================================================
--
--  STATUS & ENUM MAPPINGS (All using INT):
--  employees.status: 1=Active, 2=Probationary, 3=On Leave, 4=Resigned, 5=Terminated
--  employees.employment_type: 1=Full-time, 2=Part-time, 3=Contractual, 4=Intern
--  timesheets.status: 1=Draft, 2=Submitted, 3=Approved, 4=Rejected
--  leave_requests.status: 1=Pending, 2=Approved, 3=Rejected, 4=Cancelled
--  pay_runs.status: 1=Draft, 2=Processing, 3=Completed, 4=Cancelled
--  pay_runs.frequency: 1=Weekly, 2=Bi-weekly, 3=Semi-monthly, 4=Monthly
--  payslips.status: 1=Draft, 2=Approved, 3=Released
--  payslip_line_items.component_type: 1=Earning, 2=Deduction, 3=Tax, 4=Government
--  employee_onboarding.status: 1=Not Started, 2=In Progress, 3=Completed, 4=Cancelled
--  onboarding_task_status.status: 1=Pending, 2=In Progress, 3=Completed, 4=Skipped
--  attendance.status: 1=Present, 2=Late, 3=Absent, 4=Excused
--  benefit_enrollments.status: 1=Active, 2=Terminated, 3=Pending
--  reimbursement_requests.status: 1=Pending, 2=Approved, 3=Rejected, 4=Paid
--  audit_logs.action: 1=INSERT, 2=UPDATE, 3=DELETE
--
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
--  MODULE 1 — EMPLOYEE INFORMATION MANAGEMENT
-- ============================================================

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(160)     NOT NULL,
    username        VARCHAR(80)      NOT NULL UNIQUE,
    email           VARCHAR(160)     NOT NULL UNIQUE,
    email_verified_at TIMESTAMP      NULL,
    password        VARCHAR(255)     NOT NULL,
    remember_token  VARCHAR(100)     NULL,
    employee_id     INT UNSIGNED     NULL,
    role            TINYINT          NOT NULL DEFAULT 4 COMMENT '1=Employee,2=Supervisor,3=OIC,4=HR',
    status          VARCHAR(60)      NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE sessions (
    id              VARCHAR(255)     NOT NULL PRIMARY KEY,
    user_id         INT UNSIGNED     NULL,
    ip_address      VARCHAR(45)      NULL,
    user_agent      TEXT             NULL,
    payload         LONGTEXT         NOT NULL,
    last_activity   INT              NOT NULL,
    INDEX idx_sessions_user_id (user_id),
    INDEX idx_sessions_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE departments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120)     NOT NULL,
    parent_dept_id   INT UNSIGNED     NULL,
    created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE positions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(120)     NOT NULL,
    level           VARCHAR(60)      NULL COMMENT 'e.g. Junior, Senior, Lead, Manager',
    department_id   INT UNSIGNED     NULL,
    min_salary      DECIMAL(14,2)    NULL,
    max_salary      DECIMAL(14,2)    NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE employees (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_code       VARCHAR(30)      NOT NULL UNIQUE COMMENT 'e.g. EMP-0001',
    first_name          VARCHAR(80)      NOT NULL,
    last_name           VARCHAR(80)      NOT NULL,
    middle_name         VARCHAR(80)      NULL,
    email               VARCHAR(160)     NOT NULL UNIQUE,
    phone               VARCHAR(30)      NULL,
    birth_date          DATE             NULL,
    gender              ENUM('Male','Female','Non-binary','Prefer not to say') NULL,
    nationality         VARCHAR(80)      NULL,
    marital_status      ENUM('Single','Married','Widowed','Divorced','Separated') NULL,
    address_line1       VARCHAR(200)     NULL,
    address_line2       VARCHAR(200)     NULL,
    city                VARCHAR(100)     NULL,
    province            VARCHAR(100)     NULL,
    postal_code         VARCHAR(20)      NULL,
    country             VARCHAR(80)      NULL DEFAULT 'Philippines',
    status              INT              NOT NULL DEFAULT 2 COMMENT '1=Active, 2=Probationary, 3=On Leave, 4=Resigned, 5=Terminated',
    employment_type     INT              NOT NULL DEFAULT 1 COMMENT '1=Full-time, 2=Part-time, 3=Contractual, 4=Intern',
    hire_date           DATE             NOT NULL,
    regularization_date DATE             NULL,
    termination_date    DATE             NULL,
    termination_reason  TEXT             NULL,
    position_id         INT UNSIGNED     NULL,
    department_id       INT UNSIGNED     NULL,
    manager_id          INT UNSIGNED     NULL,
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE emergency_contacts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    full_name       VARCHAR(160)     NOT NULL,
    relationship    VARCHAR(60)      NOT NULL,
    phone           VARCHAR(30)      NOT NULL,
    alt_phone       VARCHAR(30)      NULL,
    address         VARCHAR(300)     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE government_ids (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    id_type         VARCHAR(60)      NOT NULL COMMENT 'e.g. SSS, PhilHealth, Pag-IBIG, TIN, Passport',
    id_number       VARCHAR(80)      NOT NULL,
    issued_date     DATE             NULL,
    expiry_date     DATE             NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS salary_records;
CREATE TABLE salary_records (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    amount          DECIMAL(14,2)    NOT NULL,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    pay_frequency   INT              NOT NULL DEFAULT 4 COMMENT '1=Hourly, 2=Daily, 3=Semi-monthly, 4=Monthly',
    effective_date  DATE             NOT NULL,
    end_date        DATE             NULL,
    reason          VARCHAR(200)     NULL COMMENT 'e.g. Promotion, Annual review',
    notes           VARCHAR(300)     NULL,
    created_by      INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE employee_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    doc_type        VARCHAR(80)      NOT NULL COMMENT 'e.g. Contract, NBI Clearance, Diploma',
    file_name       VARCHAR(200)     NOT NULL,
    file_url        VARCHAR(500)     NOT NULL,
    file_size_kb    INT UNSIGNED     NULL,
    issued_date     DATE             NULL,
    expiry_date     DATE             NULL,
    uploaded_by     INT UNSIGNED     NULL,
    uploaded_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 2 — EMPLOYEE ONBOARDING
-- ============================================================
-- NOTE: Several onboarding/timekeeping/benefits/self-service tables
-- were archived to the ARCHIVE section at the end of this file because
-- an automated scan found no references in the codebase. See ARCHIVE below.


-- ============================================================
--  MODULE 3 — TIMEKEEPING AND ATTENDANCE
-- ============================================================

CREATE TABLE shifts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(80)      NOT NULL,
    shift_order     INT              NOT NULL DEFAULT 1 COMMENT 'Order for sorting shifts (1st, 2nd, etc.)',
    start_time      TIME             NOT NULL,
    end_time        TIME             NOT NULL,
    break_minutes   INT              NOT NULL DEFAULT 60,
    is_night_shift  TINYINT          NOT NULL DEFAULT 0 COMMENT 'Flag for night shift differentials',
    crosses_midnight TINYINT         NOT NULL DEFAULT 0 COMMENT 'True if shift runs past midnight (e.g., 10pm-7am)',
    shift_duration_minutes INT       NULL COMMENT 'Total shift duration in minutes (excludes breaks)',
    days_of_week    VARCHAR(20)      NOT NULL COMMENT 'e.g. Mon-Fri, CSV bitmask',
    is_active       TINYINT          NOT NULL DEFAULT 1,
    INDEX idx_shifts_active_order (is_active, shift_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE shift_assignments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    shift_id        INT UNSIGNED     NOT NULL,
    effective_from  DATE             NOT NULL,
    effective_to    DATE             NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE time_logs (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id         INT UNSIGNED     NOT NULL,
    log_date            DATE             NOT NULL,
    clock_in            DATETIME         NOT NULL,
    clock_out           DATETIME         NULL,
    source              ENUM('Biometric','Web','Mobile','Manual') NOT NULL DEFAULT 'Manual',
    biometric_device_id VARCHAR(60)      NULL,
    is_remote           TINYINT          NOT NULL DEFAULT 0,
    ip_address          VARCHAR(45)      NULL,
    late_minutes        INT              NOT NULL DEFAULT 0,
    undertime_minutes   INT              NOT NULL DEFAULT 0,
    total_hours         DECIMAL(5,2)     NULL COMMENT 'Computed after clock-out',
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE break_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time_log_id     INT UNSIGNED     NOT NULL,
    break_start     DATETIME         NOT NULL,
    break_end       DATETIME         NULL,
    break_type      ENUM('Lunch','Rest','Other') NOT NULL DEFAULT 'Lunch'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE late_deductions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time_log_id         INT UNSIGNED     NULL UNIQUE COMMENT 'Reference to time_logs table',
    employee_id         INT UNSIGNED     NULL COMMENT 'Reference to employees table',
    attendance_date     DATE             NOT NULL COMMENT 'Date of the late attendance',
    expected_time       TIME             NOT NULL COMMENT 'Expected clock-in time (shift start)',
    actual_time         TIME             NOT NULL COMMENT 'Actual clock-in time',
    late_minutes        INT              NOT NULL COMMENT 'Total minutes late',
    deduction_type      ENUM('none','grace_period','one_hour','half_day','absent') NOT NULL DEFAULT 'none' COMMENT 'Type of deduction applied',
    deduction_hours     DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Hours deducted from pay',
    hourly_rate         DECIMAL(10,2)    NULL COMMENT 'Hourly rate for calculation',
    deduction_amount    DECIMAL(10,2)    NULL COMMENT 'Amount deducted from salary',
    policy_version      VARCHAR(20)      NOT NULL DEFAULT '1.0' COMMENT 'Version of late policy applied',
    is_excused          TINYINT          NOT NULL DEFAULT 0 COMMENT 'Whether late was excused/waived',
    excuse_reason       TEXT             NULL COMMENT 'Reason for excuse if applicable',
    approved_by         INT UNSIGNED     NULL COMMENT 'HR/Manager who approved/waived',
    notes               TEXT             NULL,
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_emp_date (employee_id, attendance_date),
    INDEX idx_ded_type (deduction_type),
    INDEX idx_is_excused (is_excused),
    CONSTRAINT late_deductions_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT late_deductions_time_log_id_foreign FOREIGN KEY (time_log_id) REFERENCES time_logs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT late_deductions_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks late clock-in deductions and penalties';


CREATE TABLE timesheets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    period_start    DATE             NOT NULL,
    period_end      DATE             NOT NULL,
    regular_hours   DECIMAL(6,2)     NOT NULL DEFAULT 0,
    overtime_hours  DECIMAL(6,2)     NOT NULL DEFAULT 0,
    late_hours      DECIMAL(6,2)     NOT NULL DEFAULT 0,
    absent_days     INT              NOT NULL DEFAULT 0,
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Draft, 2=Submitted, 3=Approved, 4=Rejected',
    submitted_at    DATETIME         NULL,
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    remarks         TEXT             NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 4 — LEAVE AND ABSENCE MANAGEMENT
-- ============================================================

CREATE TABLE leave_types (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(80)      NOT NULL,
    code                VARCHAR(20)      NOT NULL UNIQUE COMMENT 'e.g. VL, SL, PL, ML, PaTL',
    is_paid             TINYINT          NOT NULL DEFAULT 1,
    max_days_per_year   DECIMAL(5,1)     NULL,
    is_accrued          TINYINT          NOT NULL DEFAULT 0,
    accrual_rate        DECIMAL(5,2)     NULL COMMENT 'Days accrued per month',
    requires_approval   TINYINT          NOT NULL DEFAULT 1,
    min_notice_days     INT              NOT NULL DEFAULT 0,
    is_active           TINYINT          NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE leave_balances (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    leave_type_id   INT UNSIGNED     NOT NULL,
    year            YEAR             NOT NULL,
    entitled_days   DECIMAL(5,1)     NOT NULL DEFAULT 0,
    used_days       DECIMAL(5,1)     NOT NULL DEFAULT 0,
    accrued_days    DECIMAL(5,1)     NOT NULL DEFAULT 0,
    carried_over    DECIMAL(5,1)     NOT NULL DEFAULT 0,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lb_emp_type_year (employee_id, leave_type_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE leave_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    leave_type_id   INT UNSIGNED     NOT NULL,
    start_date      DATE             NOT NULL,
    end_date        DATE             NOT NULL,
    days_requested  DECIMAL(5,1)     NOT NULL,
    reason          TEXT             NULL,
    status          TINYINT          NOT NULL DEFAULT 1 COMMENT '1=Pending, 2=Approved, 3=Rejected, 4=Cancelled',
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    rejection_note  TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE holiday_calendars (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)     NOT NULL,
    year            YEAR             NOT NULL,
    country_code    CHAR(2)          NOT NULL DEFAULT 'PH',
    is_active       TINYINT          NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE public_holidays (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_id     INT UNSIGNED     NOT NULL,
    holiday_date    DATE             NOT NULL,
    name            VARCHAR(120)     NOT NULL,
    holiday_type    ENUM('Regular','Special Non-working','Special Working') NOT NULL DEFAULT 'Regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 5 — PAYROLL PROCESSING
-- ============================================================

CREATE TABLE pay_runs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)     NOT NULL COMMENT 'e.g. June 2026 - 1st Half',
    period_start    DATE             NOT NULL,
    period_end      DATE             NOT NULL,
    pay_date        DATE             NOT NULL,
    frequency       INT              NOT NULL DEFAULT 4 COMMENT '1=Weekly, 2=Bi-weekly, 3=Semi-monthly, 4=Monthly',
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Draft, 2=Processing, 3=Completed, 4=Cancelled',
    created_by      INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finalized_at    DATETIME         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE payrolls (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED     NOT NULL,
    payroll_date    DATE             NOT NULL,
    gross_salary    DECIMAL(12,2)    NOT NULL,
    deductions      DECIMAL(12,2)    NOT NULL DEFAULT 0,
    net_salary      DECIMAL(12,2)    NOT NULL,
    status          TINYINT          NOT NULL DEFAULT 1 COMMENT '1=processing, 2=completed, 3=failed',
    created_at      DATETIME         NULL,
    updated_at      DATETIME         NULL,
    INDEX idx_payrolls_user_id (user_id),
    INDEX idx_payrolls_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE payslips (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pay_run_id      INT UNSIGNED     NOT NULL,
    employee_id     INT UNSIGNED     NOT NULL,
    gross_pay       DECIMAL(14,2)    NOT NULL DEFAULT 0,
    total_deductions DECIMAL(14,2)   NOT NULL DEFAULT 0,
    net_pay         DECIMAL(14,2)    NOT NULL DEFAULT 0,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Draft, 2=Approved, 3=Released',
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    released_at     DATETIME         NULL,
    UNIQUE KEY uq_payslip (pay_run_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE payslip_line_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payslip_id      INT UNSIGNED     NOT NULL,
    component_type  INT              NOT NULL COMMENT '1=Earning, 2=Deduction, 3=Tax, 4=Government',
    description     VARCHAR(120)     NOT NULL COMMENT 'e.g. Basic Pay, Overtime, SSS, PhilHealth, Pag-IBIG, Withholding Tax',
    amount          DECIMAL(14,2)    NOT NULL,
    is_taxable      TINYINT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE government_contributions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payslip_id          INT UNSIGNED     NOT NULL,
    contribution_type   VARCHAR(60)      NOT NULL COMMENT 'e.g. SSS, PhilHealth, Pag-IBIG',
    employee_share      DECIMAL(10,2)    NOT NULL DEFAULT 0,
    employer_share      DECIMAL(10,2)    NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS tax_brackets;
CREATE TABLE tax_brackets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    threshold       DECIMAL(14,2)    NOT NULL,
    rate            DECIMAL(5,4)     NOT NULL,
    label           VARCHAR(120)     NULL,
    notes           TEXT             NULL,
    is_active       TINYINT          NOT NULL DEFAULT 1,
    sort_order      INT              NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS government_contribution_rates (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(120)     NOT NULL,
    employee_rate       DECIMAL(5,4)     NOT NULL,
    employer_rate       DECIMAL(5,4)     NOT NULL,
    description         TEXT             NULL,
    is_active           TINYINT          NOT NULL DEFAULT 1,
    sort_order          INT              NOT NULL DEFAULT 0,
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS deduction_rules;
CREATE TABLE deduction_rules (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(120)     NOT NULL,
    type                ENUM('Fixed','Percentage','Prorated') NOT NULL DEFAULT 'Fixed',
    amount              DECIMAL(10,2)    NULL,
    rate                DECIMAL(5,4)     NULL,
    scope               VARCHAR(120)     NULL,
    description         TEXT             NULL,
    is_active           TINYINT          NOT NULL DEFAULT 1,
    sort_order          INT              NOT NULL DEFAULT 0,
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employee_tax_bracket (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    tax_bracket_id  INT UNSIGNED     NOT NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_tax_bracket (employee_id, tax_bracket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employee_government_contribution (
    id                                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id                         INT UNSIGNED     NOT NULL,
    government_contribution_rate_id     INT UNSIGNED     NOT NULL,
    created_at                          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY emp_gov_contrib_unique (employee_id, government_contribution_rate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employee_deduction_rule (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id         INT UNSIGNED     NOT NULL,
    deduction_rule_id   INT UNSIGNED     NOT NULL,
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_deduction_rule (employee_id, deduction_rule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  MODULE 6 — BENEFITS ADMINISTRATION
-- ============================================================

CREATE TABLE benefit_plans (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)     NOT NULL,
    benefit_type    VARCHAR(80)      NOT NULL COMMENT 'e.g. Health Insurance, Retirement, Allowance, Flexible',
    provider        VARCHAR(120)     NULL,
    coverage_details TEXT            NULL,
    employer_cost   DECIMAL(10,2)    NULL,
    employee_cost   DECIMAL(10,2)    NULL,
    is_active       TINYINT          NOT NULL DEFAULT 1,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE benefit_eligibility (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id                 INT UNSIGNED     NOT NULL,
    employment_type         ENUM('Full-time','Part-time','Contractual','Intern') NULL COMMENT 'NULL = all types',
    min_tenure_months       INT              NOT NULL DEFAULT 0,
    eligible_departments    TEXT             NULL COMMENT 'JSON array of dept IDs, NULL = all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE benefit_enrollments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    plan_id         INT UNSIGNED     NOT NULL,
    enrollment_date DATE             NOT NULL,
    coverage_start  DATE             NOT NULL,
    coverage_end    DATE             NULL,
    status          INT              NOT NULL DEFAULT 3 COMMENT '1=Active, 2=Terminated, 3=Pending',
    enrolled_by     INT UNSIGNED     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE benefit_dependents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id   INT UNSIGNED     NOT NULL,
    full_name       VARCHAR(160)     NOT NULL,
    relationship    VARCHAR(60)      NOT NULL COMMENT 'e.g. Spouse, Child, Parent',
    birth_date      DATE             NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE allowances (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    allowance_type  VARCHAR(80)      NOT NULL COMMENT 'e.g. Transportation, Meal, Clothing',
    amount          DECIMAL(10,2)    NOT NULL,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    frequency       ENUM('Monthly','Per Payroll','Annually','One-time') NOT NULL DEFAULT 'Monthly',
    is_taxable      TINYINT          NOT NULL DEFAULT 0,
    effective_date  DATE             NOT NULL,
    end_date        DATE             NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 7 — SELF-SERVICE PORTAL
-- ============================================================

CREATE TABLE reimbursement_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    category        VARCHAR(80)      NOT NULL COMMENT 'e.g. Travel, Medical, Training',
    description     TEXT             NULL,
    amount          DECIMAL(10,2)    NOT NULL,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    expense_date    DATE             NOT NULL,
    receipt_url     VARCHAR(500)     NULL,
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Pending, 2=Approved, 3=Rejected, 4=Paid',
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    paid_at         DATETIME         NULL,
    rejection_note  TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE policy_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200)     NOT NULL,
    category        VARCHAR(80)      NULL COMMENT 'e.g. Code of Conduct, Benefits, Safety',
    file_url        VARCHAR(500)     NOT NULL,
    version         VARCHAR(20)      NULL,
    applies_to      ENUM('All','Full-time','Part-time','Contractual','Intern') NOT NULL DEFAULT 'All',
    department_id   INT UNSIGNED     NULL COMMENT 'NULL = company-wide',
    published_at    DATE             NULL,
    is_active       TINYINT          NOT NULL DEFAULT 1,
    uploaded_by     INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE notifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_id    INT UNSIGNED     NOT NULL,
    type            VARCHAR(60)      NOT NULL COMMENT 'e.g. leave_approved, payslip_ready, task_due',
    title           VARCHAR(160)     NOT NULL,
    message         TEXT             NULL,
    link            VARCHAR(300)     NULL,
    is_read         TINYINT          NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE portal_activity_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    action          VARCHAR(120)     NOT NULL COMMENT 'e.g. login, view_payslip, submit_leave',
    module          VARCHAR(80)      NULL,
    record_id       INT UNSIGNED     NULL,
    ip_address      VARCHAR(45)      NULL,
    user_agent      VARCHAR(300)     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 8 — HR REPORTING AND DASHBOARDS
-- ============================================================

CREATE TABLE report_definitions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(160)     NOT NULL,
    module          VARCHAR(80)      NOT NULL COMMENT 'e.g. Attendance, Payroll, Headcount',
    description     TEXT             NULL,
    filters_json    JSON             NULL COMMENT 'Saved filter criteria',
    columns_json    JSON             NULL COMMENT 'Selected output columns',
    is_shared       TINYINT          NOT NULL DEFAULT 0,
    created_by      INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE report_schedules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id       INT UNSIGNED     NOT NULL,
    frequency       ENUM('Daily','Weekly','Monthly') NOT NULL,
    day_of_week     TINYINT          NULL COMMENT '0=Sun, 6=Sat — for weekly',
    day_of_month    TINYINT          NULL COMMENT '1-31 — for monthly',
    recipients_json JSON             NOT NULL COMMENT 'Array of email addresses',
    is_active       TINYINT          NOT NULL DEFAULT 1,
    last_run_at     DATETIME         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE dashboard_widgets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id        INT UNSIGNED     NULL COMMENT 'NULL = system default',
    widget_type     VARCHAR(60)      NOT NULL COMMENT 'e.g. bar_chart, kpi_card, table',
    metric_key      VARCHAR(80)      NOT NULL COMMENT 'e.g. headcount, attrition_rate, overtime_hours',
    title           VARCHAR(120)     NULL,
    config_json     JSON             NULL COMMENT 'Filters, date range, display options',
    display_order   INT              NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE workforce_snapshots (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_date           DATE             NOT NULL UNIQUE,
    total_headcount         INT              NOT NULL DEFAULT 0,
    active_count            INT              NOT NULL DEFAULT 0,
    probationary_count      INT              NOT NULL DEFAULT 0,
    new_hires               INT              NOT NULL DEFAULT 0,
    terminations            INT              NOT NULL DEFAULT 0,
    attrition_rate          DECIMAL(5,2)     NULL COMMENT 'Percentage',
    avg_tenure_months       DECIMAL(6,1)     NULL,
    dept_breakdown_json     JSON             NULL COMMENT 'Headcount per department',
    gender_breakdown_json   JSON             NULL,
    created_at              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



/* ARCHIVE SECTION START — removed CREATE TABLE blocks
   Timestamp: 2026-05-18
   Reason: No code references detected during scan. Restore by copying
   the desired CREATE block back into the main body above this comment.

   (CREATE blocks follow — kept for reference)

*/

/*
CREATE TABLE onboarding_templates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)     NOT NULL,
    description     TEXT             NULL,
    employment_type ENUM('Full-time','Part-time','Contractual','Intern') NULL,
    is_active       TINYINT          NOT NULL DEFAULT 1,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE onboarding_tasks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id     INT UNSIGNED     NOT NULL,
    task_name       VARCHAR(160)     NOT NULL,
    category        VARCHAR(80)      NULL COMMENT 'e.g. Documents, IT Setup, Training, HR',
    description     TEXT             NULL,
    assigned_role   VARCHAR(80)      NULL COMMENT 'Who performs this task',
    due_day_offset  INT              NOT NULL DEFAULT 1 COMMENT 'Days from hire date',
    order_seq       INT              NOT NULL DEFAULT 0,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employee_onboarding (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    template_id     INT UNSIGNED     NOT NULL,
    start_date      DATE             NOT NULL,
    target_end_date DATE             NULL,
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Not Started, 2=In Progress, 3=Completed, 4=Cancelled',
    notes           TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE onboarding_task_status (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    onboarding_id   INT UNSIGNED     NOT NULL,
    task_id         INT UNSIGNED     NOT NULL,
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Pending, 2=In Progress, 3=Completed, 4=Skipped',
    completed_by    INT UNSIGNED     NULL,
    completed_at    DATETIME         NULL,
    notes           TEXT             NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE equipment_assignments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    item_name       VARCHAR(120)     NOT NULL,
    item_type       VARCHAR(80)      NULL COMMENT 'e.g. Laptop, Phone, Access Card',
    brand_model     VARCHAR(120)     NULL,
    serial_number   VARCHAR(100)     NULL,
    assigned_date   DATE             NOT NULL,
    returned_date   DATE             NULL,
    condition_out   VARCHAR(60)      NULL,
    condition_in    VARCHAR(60)      NULL,
    notes           TEXT             NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shifts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(80)      NOT NULL,
    start_time      TIME             NOT NULL,
    end_time        TIME             NOT NULL,
    break_minutes   INT              NOT NULL DEFAULT 60,
    is_night_shift  TINYINT          NOT NULL DEFAULT 0,
    days_of_week    VARCHAR(20)      NOT NULL COMMENT 'e.g. Mon-Fri, CSV bitmask',
    is_active       TINYINT          NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shift_assignments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    shift_id        INT UNSIGNED     NOT NULL,
    effective_from  DATE             NOT NULL,
    effective_to    DATE             NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE time_logs (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id         INT UNSIGNED     NOT NULL,
    log_date            DATE             NOT NULL,
    clock_in            DATETIME         NOT NULL,
    clock_out           DATETIME         NULL,
    source              ENUM('Biometric','Web','Mobile','Manual') NOT NULL DEFAULT 'Manual',
    biometric_device_id VARCHAR(60)      NULL,
    is_remote           TINYINT          NOT NULL DEFAULT 0,
    ip_address          VARCHAR(45)      NULL,
    late_minutes        INT              NOT NULL DEFAULT 0,
    undertime_minutes   INT              NOT NULL DEFAULT 0,
    total_hours         DECIMAL(5,2)     NULL COMMENT 'Computed after clock-out',
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE break_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time_log_id     INT UNSIGNED     NOT NULL,
    break_start     DATETIME         NOT NULL,
    break_end       DATETIME         NULL,
    break_type      ENUM('Lunch','Rest','Other') NOT NULL DEFAULT 'Lunch',
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE timesheets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    period_start    DATE             NOT NULL,
    period_end      DATE             NOT NULL,
    regular_hours   DECIMAL(6,2)     NOT NULL DEFAULT 0,
    overtime_hours  DECIMAL(6,2)     NOT NULL DEFAULT 0,
    late_hours      DECIMAL(6,2)     NOT NULL DEFAULT 0,
    absent_days     INT              NOT NULL DEFAULT 0,
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Draft, 2=Submitted, 3=Approved, 4=Rejected',
    submitted_at    DATETIME         NULL,
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    remarks         TEXT             NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bank_accounts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    bank_name       VARCHAR(100)     NOT NULL,
    account_name    VARCHAR(160)     NOT NULL,
    account_number  VARCHAR(60)      NOT NULL,
    is_primary      TINYINT          NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE benefit_plans (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)     NOT NULL,
    benefit_type    VARCHAR(80)      NOT NULL COMMENT 'e.g. Health Insurance, Retirement, Allowance, Flexible',
    provider        VARCHAR(120)     NULL,
    coverage_details TEXT            NULL,
    employer_cost   DECIMAL(10,2)    NULL,
    employee_cost   DECIMAL(10,2)    NULL,
    is_active       TINYINT          NOT NULL DEFAULT 1,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE benefit_eligibility (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id                 INT UNSIGNED     NOT NULL,
    employment_type         ENUM('Full-time','Part-time','Contractual','Intern') NULL COMMENT 'NULL = all types',
    min_tenure_months       INT              NOT NULL DEFAULT 0,
    eligible_departments    TEXT             NULL COMMENT 'JSON array of dept IDs, NULL = all',
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE benefit_enrollments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    plan_id         INT UNSIGNED     NOT NULL,
    enrollment_date DATE             NOT NULL,
    coverage_start  DATE             NOT NULL,
    coverage_end    DATE             NULL,
    status          INT              NOT NULL DEFAULT 3 COMMENT '1=Active, 2=Terminated, 3=Pending',
    enrolled_by     INT UNSIGNED     NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE benefit_dependents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id   INT UNSIGNED     NOT NULL,
    full_name       VARCHAR(160)     NOT NULL,
    relationship    VARCHAR(60)      NOT NULL COMMENT 'e.g. Spouse, Child, Parent',
    birth_date      DATE             NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE allowances (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    allowance_type  VARCHAR(80)      NOT NULL COMMENT 'e.g. Transportation, Meal, Clothing',
    amount          DECIMAL(10,2)    NOT NULL,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    frequency       ENUM('Monthly','Per Payroll','Annually','One-time') NOT NULL DEFAULT 'Monthly',
    is_taxable      TINYINT          NOT NULL DEFAULT 0,
    effective_date  DATE             NOT NULL,
    end_date        DATE             NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reimbursement_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    category        VARCHAR(80)      NOT NULL COMMENT 'e.g. Travel, Medical, Training',
    description     TEXT             NULL,
    amount          DECIMAL(10,2)    NOT NULL,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    expense_date    DATE             NOT NULL,
    receipt_url     VARCHAR(500)     NULL,
    status          INT              NOT NULL DEFAULT 1 COMMENT '1=Pending, 2=Approved, 3=Rejected, 4=Paid',
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    paid_at         DATETIME         NULL,
    rejection_note  TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE policy_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200)     NOT NULL,
    category        VARCHAR(80)      NULL COMMENT 'e.g. Code of Conduct, Benefits, Safety',
    file_url        VARCHAR(500)     NOT NULL,
    version         VARCHAR(20)      NULL,
    applies_to      ENUM('All','Full-time','Part-time','Contractual','Intern') NOT NULL DEFAULT 'All',
    department_id   INT UNSIGNED     NULL COMMENT 'NULL = company-wide',
    published_at    DATE             NULL,
    is_active       TINYINT          NOT NULL DEFAULT 1,
    uploaded_by     INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_id    INT UNSIGNED     NOT NULL,
    type            VARCHAR(60)      NOT NULL COMMENT 'e.g. leave_approved, payslip_ready, task_due',
    title           VARCHAR(160)     NOT NULL,
    message         TEXT             NULL,
    link            VARCHAR(300)     NULL,
    is_read         TINYINT          NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME         NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE portal_activity_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    action          VARCHAR(120)     NOT NULL COMMENT 'e.g. login, view_payslip, submit_leave',
    module          VARCHAR(80)      NULL,
    record_id       INT UNSIGNED     NULL,
    ip_address      VARCHAR(45)      NULL,
    user_agent      VARCHAR(300)     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE report_definitions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(160)     NOT NULL,
    module          VARCHAR(80)      NOT NULL COMMENT 'e.g. Attendance, Payroll, Headcount',
    description     TEXT             NULL,
    filters_json    JSON             NULL COMMENT 'Saved filter criteria',
    columns_json    JSON             NULL COMMENT 'Selected output columns',
    is_shared       TINYINT          NOT NULL DEFAULT 0,
    created_by      INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE report_schedules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id       INT UNSIGNED     NOT NULL,
    frequency       ENUM('Daily','Weekly','Monthly') NOT NULL,
    day_of_week     TINYINT          NULL COMMENT '0=Sun, 6=Sat — for weekly',
    day_of_month    TINYINT          NULL COMMENT '1-31 — for monthly',
    recipients_json JSON             NOT NULL COMMENT 'Array of email addresses',
    is_active       TINYINT          NOT NULL DEFAULT 1,
    last_run_at     DATETIME         NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dashboard_widgets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id        INT UNSIGNED     NULL COMMENT 'NULL = system default',
    widget_type     VARCHAR(60)      NOT NULL COMMENT 'e.g. bar_chart, kpi_card, table',
    metric_key      VARCHAR(80)      NOT NULL COMMENT 'e.g. headcount, attrition_rate, overtime_hours',
    title           VARCHAR(120)     NULL,
    config_json     JSON             NULL COMMENT 'Filters, date range, display options',
    display_order   INT              NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE workforce_snapshots (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_date           DATE             NOT NULL UNIQUE,
    total_headcount         INT              NOT NULL DEFAULT 0,
    active_count            INT              NOT NULL DEFAULT 0,
    probationary_count      INT              NOT NULL DEFAULT 0,
    new_hires               INT              NOT NULL DEFAULT 0,
    terminations            INT              NOT NULL DEFAULT 0,
    attrition_rate          DECIMAL(5,2)     NULL COMMENT 'Percentage',
    avg_tenure_months       DECIMAL(6,1)     NULL,
    dept_breakdown_json     JSON             NULL COMMENT 'Headcount per department',
    gender_breakdown_json   JSON             NULL,
    created_at              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id        INT UNSIGNED     NULL COMMENT 'NULL = system/automated',
    table_name      VARCHAR(80)      NOT NULL,
    record_id       VARCHAR(40)      NOT NULL,
    action          INT              NOT NULL COMMENT '1=INSERT, 2=UPDATE, 3=DELETE',
    old_values      JSON             NULL,
    new_values      JSON             NULL,
    ip_address      VARCHAR(45)      NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_al_table  (table_name),
    INDEX idx_al_actor  (actor_id),
    INDEX idx_al_time   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

*/


-- ============================================================
--  MODULE 3B — ATTENDANCE (Additional to Timekeeping)
-- ============================================================

CREATE TABLE attendance (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED     NOT NULL,
    attendance_date     DATE             NOT NULL,
    check_in            DATETIME         NULL,
    check_out           DATETIME         NULL,
    status              INT              NOT NULL DEFAULT 1 COMMENT '1=Present, 2=Late, 3=Absent, 4=Excused',
    notes               TEXT             NULL,
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_att_user_date (user_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  INDEXES FOR COMMON QUERY PATTERNS
-- ============================================================

ALTER TABLE employees          ADD INDEX idx_emp_status      (status);
ALTER TABLE employees          ADD INDEX idx_emp_dept        (department_id);
ALTER TABLE employees          ADD INDEX idx_emp_manager     (manager_id);
ALTER TABLE salary_records     ADD INDEX idx_sal_emp_date    (employee_id, effective_date);
ALTER TABLE time_logs          ADD INDEX idx_tl_emp_date     (employee_id, log_date);
ALTER TABLE timesheets         ADD INDEX idx_ts_period       (period_start, period_end);
ALTER TABLE leave_requests     ADD INDEX idx_lr_emp_status   (employee_id, status);
ALTER TABLE leave_requests     ADD INDEX idx_lr_dates        (start_date, end_date);
ALTER TABLE payslips           ADD INDEX idx_ps_run          (pay_run_id);
ALTER TABLE payslip_line_items ADD INDEX idx_pli_payslip     (payslip_id);
ALTER TABLE benefit_enrollments ADD INDEX idx_be_emp_status  (employee_id, status);
ALTER TABLE notifications      ADD INDEX idx_notif_unread    (recipient_id, is_read);
ALTER TABLE portal_activity_logs ADD INDEX idx_pal_emp_time  (employee_id, created_at);
ALTER TABLE workforce_snapshots ADD INDEX idx_ws_date        (snapshot_date);

-- ============================================================
--  SHIFT TEMPLATES (Common Shifts)
-- ============================================================

INSERT INTO shifts (name, shift_order, start_time, end_time, break_minutes, is_night_shift, crosses_midnight, shift_duration_minutes, days_of_week, is_active) VALUES
('Morning Shift (8-5)', 1, '08:00:00', '17:00:00', 60, 0, 0, 480, 'Mon-Fri', 1),
('8-4 (No Lunch)', 2, '08:00:00', '16:00:00', 0, 0, 0, 480, 'Mon-Fri', 1),
('9-6', 3, '09:00:00', '18:00:00', 60, 0, 0, 480, 'Mon-Fri', 1),
('10-7', 4, '10:00:00', '19:00:00', 60, 0, 0, 480, 'Mon-Fri', 1),
('11-8', 5, '11:00:00', '20:00:00', 60, 0, 0, 480, 'Mon-Fri', 1),
('Graveyard (10pm-7am)', 6, '22:00:00', '07:00:00', 60, 1, 1, 480, 'Mon-Fri', 1),
('Graveyard (11pm-8am)', 7, '23:00:00', '08:00:00', 60, 1, 1, 480, 'Mon-Fri', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  END OF SCRIPT
-- ============================================================
