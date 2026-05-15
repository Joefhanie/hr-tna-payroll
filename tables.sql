-- ============================================================
--  HR SYSTEM — MySQL / MariaDB
--  Modules: Employee Info, Onboarding, Timekeeping, Leave,
--           Payroll, Benefits, Self-Service, Reporting
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
--  MODULE 1 — EMPLOYEE INFORMATION MANAGEMENT
-- ============================================================

CREATE TABLE departments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120)     NOT NULL,
    parent_dept_id   INT UNSIGNED     NULL,
    created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dept_parent FOREIGN KEY (parent_dept_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE positions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(120)     NOT NULL,
    level           VARCHAR(60)      NULL COMMENT 'e.g. Junior, Senior, Lead, Manager',
    department_id   INT UNSIGNED     NULL,
    min_salary      DECIMAL(14,2)    NULL,
    max_salary      DECIMAL(14,2)    NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pos_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
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
    status              ENUM('Active','Probationary','On Leave','Resigned','Terminated') NOT NULL DEFAULT 'Probationary',
    employment_type     ENUM('Full-time','Part-time','Contractual','Intern') NOT NULL DEFAULT 'Full-time',
    hire_date           DATE             NOT NULL,
    regularization_date DATE             NULL,
    termination_date    DATE             NULL,
    termination_reason  TEXT             NULL,
    position_id         INT UNSIGNED     NULL,
    department_id       INT UNSIGNED     NULL,
    manager_id          INT UNSIGNED     NULL,
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_emp_position   FOREIGN KEY (position_id)   REFERENCES positions(id)   ON DELETE SET NULL,
    CONSTRAINT fk_emp_dept       FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_emp_manager    FOREIGN KEY (manager_id)    REFERENCES employees(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE emergency_contacts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    full_name       VARCHAR(160)     NOT NULL,
    relationship    VARCHAR(60)      NOT NULL,
    phone           VARCHAR(30)      NOT NULL,
    alt_phone       VARCHAR(30)      NULL,
    address         VARCHAR(300)     NULL,
    CONSTRAINT fk_ec_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE government_ids (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    id_type         VARCHAR(60)      NOT NULL COMMENT 'e.g. SSS, PhilHealth, Pag-IBIG, TIN, Passport',
    id_number       VARCHAR(80)      NOT NULL,
    issued_date     DATE             NULL,
    expiry_date     DATE             NULL,
    CONSTRAINT fk_gid_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE salary_records (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    amount          DECIMAL(14,2)    NOT NULL,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    pay_frequency     ENUM('Monthly','Semi-monthly','Hourly','Daily') NOT NULL DEFAULT 'Monthly',
    effective_date  DATE             NOT NULL,
    end_date        DATE             NULL,
    reason          VARCHAR(200)     NULL COMMENT 'e.g. Promotion, Annual review',
    created_by      INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sal_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
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
    uploaded_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 2 — EMPLOYEE ONBOARDING
-- ============================================================

CREATE TABLE onboarding_templates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)     NOT NULL,
    description     TEXT             NULL,
    employment_type ENUM('Full-time','Part-time','Contractual','Intern') NULL,
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
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
    CONSTRAINT fk_task_template FOREIGN KEY (template_id) REFERENCES onboarding_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE employee_onboarding (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    template_id     INT UNSIGNED     NOT NULL,
    start_date      DATE             NOT NULL,
    target_end_date DATE             NULL,
    status          ENUM('Not Started','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Not Started',
    notes           TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_eonb_emp      FOREIGN KEY (employee_id) REFERENCES employees(id)            ON DELETE CASCADE,
    CONSTRAINT fk_eonb_template FOREIGN KEY (template_id) REFERENCES onboarding_templates(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE onboarding_task_status (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    onboarding_id   INT UNSIGNED     NOT NULL,
    task_id         INT UNSIGNED     NOT NULL,
    status          ENUM('Pending','In Progress','Completed','Skipped') NOT NULL DEFAULT 'Pending',
    completed_by    INT UNSIGNED     NULL,
    completed_at    DATETIME         NULL,
    notes           TEXT             NULL,
    CONSTRAINT fk_ots_onboarding FOREIGN KEY (onboarding_id) REFERENCES employee_onboarding(id) ON DELETE CASCADE,
    CONSTRAINT fk_ots_task       FOREIGN KEY (task_id)       REFERENCES onboarding_tasks(id)    ON DELETE CASCADE
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
    CONSTRAINT fk_equip_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 3 — TIMEKEEPING AND ATTENDANCE
-- ============================================================

CREATE TABLE shifts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(80)      NOT NULL,
    start_time      TIME             NOT NULL,
    end_time        TIME             NOT NULL,
    break_minutes   INT              NOT NULL DEFAULT 60,
    is_night_shift  TINYINT(1)       NOT NULL DEFAULT 0,
    days_of_week    VARCHAR(20)      NOT NULL COMMENT 'e.g. Mon-Fri, CSV bitmask',
    is_active       TINYINT(1)       NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE shift_assignments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    shift_id        INT UNSIGNED     NOT NULL,
    effective_from  DATE             NOT NULL,
    effective_to    DATE             NULL,
    CONSTRAINT fk_sa_emp   FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_shift FOREIGN KEY (shift_id)    REFERENCES shifts(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE time_logs (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id         INT UNSIGNED     NOT NULL,
    log_date            DATE             NOT NULL,
    clock_in            DATETIME         NOT NULL,
    clock_out           DATETIME         NULL,
    source              ENUM('Biometric','Web','Mobile','Manual') NOT NULL DEFAULT 'Manual',
    biometric_device_id VARCHAR(60)      NULL,
    is_remote           TINYINT(1)       NOT NULL DEFAULT 0,
    ip_address          VARCHAR(45)      NULL,
    late_minutes        INT              NOT NULL DEFAULT 0,
    undertime_minutes   INT              NOT NULL DEFAULT 0,
    total_hours         DECIMAL(5,2)     NULL COMMENT 'Computed after clock-out',
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tl_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE break_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time_log_id     INT UNSIGNED     NOT NULL,
    break_start     DATETIME         NOT NULL,
    break_end       DATETIME         NULL,
    break_type      ENUM('Lunch','Rest','Other') NOT NULL DEFAULT 'Lunch',
    CONSTRAINT fk_bl_timelog FOREIGN KEY (time_log_id) REFERENCES time_logs(id) ON DELETE CASCADE
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
    status          ENUM('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
    submitted_at    DATETIME         NULL,
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    remarks         TEXT             NULL,
    CONSTRAINT fk_ts_emp      FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_ts_approver FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  MODULE 4 — LEAVE AND ABSENCE MANAGEMENT
-- ============================================================

CREATE TABLE leave_types (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(80)      NOT NULL,
    code                VARCHAR(20)      NOT NULL UNIQUE COMMENT 'e.g. VL, SL, PL, ML, PaTL',
    is_paid             TINYINT(1)       NOT NULL DEFAULT 1,
    max_days_per_year   DECIMAL(5,1)     NULL,
    is_accrued          TINYINT(1)       NOT NULL DEFAULT 0,
    accrual_rate        DECIMAL(5,2)     NULL COMMENT 'Days accrued per month',
    requires_approval   TINYINT(1)       NOT NULL DEFAULT 1,
    min_notice_days     INT              NOT NULL DEFAULT 0,
    is_active           TINYINT(1)       NOT NULL DEFAULT 1
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
    UNIQUE KEY uq_lb_emp_type_year (employee_id, leave_type_id, year),
    CONSTRAINT fk_lb_emp  FOREIGN KEY (employee_id)   REFERENCES employees(id)   ON DELETE CASCADE,
    CONSTRAINT fk_lb_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE leave_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    leave_type_id   INT UNSIGNED     NOT NULL,
    start_date      DATE             NOT NULL,
    end_date        DATE             NOT NULL,
    days_requested  DECIMAL(5,1)     NOT NULL,
    reason          TEXT             NULL,
    status          ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    rejection_note  TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lr_emp      FOREIGN KEY (employee_id)   REFERENCES employees(id)   ON DELETE CASCADE,
    CONSTRAINT fk_lr_type     FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_lr_approver FOREIGN KEY (approved_by)   REFERENCES employees(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE holiday_calendars (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)     NOT NULL,
    year            YEAR             NOT NULL,
    country_code    CHAR(2)          NOT NULL DEFAULT 'PH',
    is_active       TINYINT(1)       NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE public_holidays (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_id     INT UNSIGNED     NOT NULL,
    holiday_date    DATE             NOT NULL,
    name            VARCHAR(120)     NOT NULL,
    holiday_type    ENUM('Regular','Special Non-working','Special Working') NOT NULL DEFAULT 'Regular',
    CONSTRAINT fk_ph_calendar FOREIGN KEY (calendar_id) REFERENCES holiday_calendars(id) ON DELETE CASCADE
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
    frequency       ENUM('Monthly','Semi-monthly','Weekly','Bi-weekly') NOT NULL DEFAULT 'Semi-monthly',
    status          ENUM('Draft','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
    created_by      INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalized_at    DATETIME         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE payslips (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pay_run_id      INT UNSIGNED     NOT NULL,
    employee_id     INT UNSIGNED     NOT NULL,
    gross_pay       DECIMAL(14,2)    NOT NULL DEFAULT 0,
    total_deductions DECIMAL(14,2)   NOT NULL DEFAULT 0,
    net_pay         DECIMAL(14,2)    NOT NULL DEFAULT 0,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    status          ENUM('Draft','Approved','Released') NOT NULL DEFAULT 'Draft',
    released_at     DATETIME         NULL,
    UNIQUE KEY uq_payslip (pay_run_id, employee_id),
    CONSTRAINT fk_ps_run FOREIGN KEY (pay_run_id)   REFERENCES pay_runs(id)   ON DELETE CASCADE,
    CONSTRAINT fk_ps_emp FOREIGN KEY (employee_id)  REFERENCES employees(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE payslip_line_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payslip_id      INT UNSIGNED     NOT NULL,
    component_type  ENUM('Earning','Deduction','Tax','Government') NOT NULL,
    description     VARCHAR(120)     NOT NULL COMMENT 'e.g. Basic Pay, Overtime, SSS, PhilHealth, Pag-IBIG, Withholding Tax',
    amount          DECIMAL(14,2)    NOT NULL,
    is_taxable      TINYINT(1)       NOT NULL DEFAULT 0,
    CONSTRAINT fk_pli_payslip FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE government_contributions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payslip_id          INT UNSIGNED     NOT NULL,
    contribution_type   VARCHAR(60)      NOT NULL COMMENT 'e.g. SSS, PhilHealth, Pag-IBIG',
    employee_share      DECIMAL(10,2)    NOT NULL DEFAULT 0,
    employer_share      DECIMAL(10,2)    NOT NULL DEFAULT 0,
    CONSTRAINT fk_gc_payslip FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE tax_brackets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_code    CHAR(2)          NOT NULL DEFAULT 'PH',
    year            YEAR             NOT NULL,
    min_income      DECIMAL(14,2)    NOT NULL,
    max_income      DECIMAL(14,2)    NULL COMMENT 'NULL = no upper bound',
    base_tax        DECIMAL(14,2)    NOT NULL DEFAULT 0,
    rate            DECIMAL(5,4)     NOT NULL COMMENT 'e.g. 0.20 = 20%',
    excess_over     DECIMAL(14,2)    NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE bank_accounts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    bank_name       VARCHAR(100)     NOT NULL,
    account_name    VARCHAR(160)     NOT NULL,
    account_number  VARCHAR(60)      NOT NULL,
    is_primary      TINYINT(1)       NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ba_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
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
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE benefit_eligibility (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id                 INT UNSIGNED     NOT NULL,
    employment_type         ENUM('Full-time','Part-time','Contractual','Intern') NULL COMMENT 'NULL = all types',
    min_tenure_months       INT              NOT NULL DEFAULT 0,
    eligible_departments    TEXT             NULL COMMENT 'JSON array of dept IDs, NULL = all',
    CONSTRAINT fk_be_plan FOREIGN KEY (plan_id) REFERENCES benefit_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE benefit_enrollments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    plan_id         INT UNSIGNED     NOT NULL,
    enrollment_date DATE             NOT NULL,
    coverage_start  DATE             NOT NULL,
    coverage_end    DATE             NULL,
    status          ENUM('Active','Terminated','Pending') NOT NULL DEFAULT 'Active',
    enrolled_by     INT UNSIGNED     NULL,
    CONSTRAINT fk_ben_emp  FOREIGN KEY (employee_id) REFERENCES employees(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ben_plan FOREIGN KEY (plan_id)     REFERENCES benefit_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE benefit_dependents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id   INT UNSIGNED     NOT NULL,
    full_name       VARCHAR(160)     NOT NULL,
    relationship    VARCHAR(60)      NOT NULL COMMENT 'e.g. Spouse, Child, Parent',
    birth_date      DATE             NULL,
    CONSTRAINT fk_bd_enrollment FOREIGN KEY (enrollment_id) REFERENCES benefit_enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE allowances (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED     NOT NULL,
    allowance_type  VARCHAR(80)      NOT NULL COMMENT 'e.g. Transportation, Meal, Clothing',
    amount          DECIMAL(10,2)    NOT NULL,
    currency        CHAR(3)          NOT NULL DEFAULT 'PHP',
    frequency       ENUM('Monthly','Per Payroll','Annually','One-time') NOT NULL DEFAULT 'Monthly',
    is_taxable      TINYINT(1)       NOT NULL DEFAULT 0,
    effective_date  DATE             NOT NULL,
    end_date        DATE             NULL,
    CONSTRAINT fk_allow_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
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
    status          ENUM('Pending','Approved','Rejected','Paid') NOT NULL DEFAULT 'Pending',
    approved_by     INT UNSIGNED     NULL,
    approved_at     DATETIME         NULL,
    paid_at         DATETIME         NULL,
    rejection_note  TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rr_emp      FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_rr_approver FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
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
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
    uploaded_by     INT UNSIGNED     NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pd_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE notifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_id    INT UNSIGNED     NOT NULL,
    type            VARCHAR(60)      NOT NULL COMMENT 'e.g. leave_approved, payslip_ready, task_due',
    title           VARCHAR(160)     NOT NULL,
    message         TEXT             NULL,
    link            VARCHAR(300)     NULL,
    is_read         TINYINT(1)       NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME         NULL,
    CONSTRAINT fk_notif_emp FOREIGN KEY (recipient_id) REFERENCES employees(id) ON DELETE CASCADE
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
    CONSTRAINT fk_pal_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
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
    is_shared       TINYINT(1)       NOT NULL DEFAULT 0,
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
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
    last_run_at     DATETIME         NULL,
    CONSTRAINT fk_rs_report FOREIGN KEY (report_id) REFERENCES report_definitions(id) ON DELETE CASCADE
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
    action          ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    old_values      JSON             NULL,
    new_values      JSON             NULL,
    ip_address      VARCHAR(45)      NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_al_table  (table_name),
    INDEX idx_al_actor  (actor_id),
    INDEX idx_al_time   (created_at)
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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  END OF SCRIPT
-- ============================================================
