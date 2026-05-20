START TRANSACTION;

-- Adjust these IDs to match your database records.
SET @user_id = 4;
SET @employee_id = 4;
SET @pay_run_id = 1;
SET @payslip_id = 1;

-- ============================================================
-- ATTENDANCE RECORDS
-- attendance = daily status summary
-- status: 1=Present, 2=Late, 3=Absent, 4=Excused
-- ============================================================

INSERT INTO attendance
    (user_id, attendance_date, check_in, check_out, status, notes, created_at, updated_at)
VALUES
    (@user_id, '2026-05-19', '2026-05-19 07:55:00', '2026-05-19 17:05:00', 1, 'On time', NOW(), NOW()),
    (@user_id, '2026-05-20', '2026-05-20 08:37:00', '2026-05-20 17:00:00', 2, 'Late arrival', NOW(), NOW()),
    (@user_id, '2026-05-21', NULL, NULL, 3, 'Absent', NOW(), NOW()),
    (@user_id, '2026-05-22', '2026-05-22 07:58:00', '2026-05-22 16:15:00', 1, 'Undertime example', NOW(), NOW()),
    (@user_id, '2026-05-23', '2026-05-23 08:00:00', '2026-05-23 19:30:00', 1, 'Overtime example', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    check_in = VALUES(check_in),
    check_out = VALUES(check_out),
    status = VALUES(status),
    notes = VALUES(notes),
    updated_at = NOW();

-- ============================================================
-- TIME LOGS
-- time_logs = detailed payroll basis for late_minutes, undertime_minutes, overtime
-- ============================================================

INSERT INTO time_logs
    (employee_id, log_date, clock_in, clock_out, source, biometric_device_id, is_remote, ip_address, late_minutes, undertime_minutes, total_hours, created_at)
VALUES
    (@employee_id, '2026-05-19', '2026-05-19 07:55:00', '2026-05-19 17:05:00', 'Manual', NULL, 0, NULL, 0, 0, 8.17, NOW()),
    (@employee_id, '2026-05-20', '2026-05-20 08:37:00', '2026-05-20 17:00:00', 'Manual', NULL, 0, NULL, 37, 0, 7.38, NOW()),
    (@employee_id, '2026-05-21', '2026-05-21 00:00:00', NULL, 'Manual', NULL, 0, NULL, 0, 0, NULL, NOW()),
    (@employee_id, '2026-05-22', '2026-05-22 07:58:00', '2026-05-22 16:15:00', 'Manual', NULL, 0, NULL, 0, 45, 7.28, NOW()),
    (@employee_id, '2026-05-23', '2026-05-23 08:00:00', '2026-05-23 19:30:00', 'Manual', NULL, 0, NULL, 0, 0, 10.50, NOW())
ON DUPLICATE KEY UPDATE
    clock_in = VALUES(clock_in),
    clock_out = VALUES(clock_out),
    source = VALUES(source),
    biometric_device_id = VALUES(biometric_device_id),
    is_remote = VALUES(is_remote),
    ip_address = VALUES(ip_address),
    late_minutes = VALUES(late_minutes),
    undertime_minutes = VALUES(undertime_minutes),
    total_hours = VALUES(total_hours);

-- ============================================================
-- GOVERNMENT CONTRIBUTION RATES
-- Reference values based on the user's provided contribution guide.
-- Withholding tax belongs in tax_brackets, not this table.
-- ============================================================

DELETE FROM government_contribution_rates
WHERE name IN ('SSS', 'PhilHealth', 'Pag-IBIG');

INSERT INTO government_contribution_rates
    (name, employee_rate, employer_rate, description, is_active, sort_order, created_at, updated_at)
VALUES
    ('SSS', 0.0500, 0.1000, 'Approximate split based on the provided reference: employee 5%, employer 10%. Actual SSS uses the official MSC table.', 1, 1, NOW(), NOW()),
    ('PhilHealth', 0.0250, 0.0250, 'Shared equally at 5% total contribution under the provided reference. Actual rates remain subject to PhilHealth rules and salary caps.', 1, 2, NOW(), NOW()),
    ('Pag-IBIG', 0.0200, 0.0200, 'Standard simplified rate using the common 2% employee / 2% employer setup. Actual Pag-IBIG computation may vary by salary bracket and ceiling.', 1, 3, NOW(), NOW());

-- ============================================================
-- PAYSLIP LINE ITEMS
-- component_type: 1=Earning, 2=Deduction, 3=Tax, 4=Government
-- Use these to store overtime, premium, late differential, and deductions
-- ============================================================

INSERT INTO payslip_line_items
    (payslip_id, component_type, description, amount, is_taxable)
VALUES
    (@payslip_id, 1, 'Basic Pay', 15000.00, 1),
    (@payslip_id, 1, 'Overtime Pay', 850.00, 1),
    (@payslip_id, 1, 'Night Differential / Premium', 300.00, 1),
    (@payslip_id, 1, 'Late Differential Bonus', 150.00, 1),
    (@payslip_id, 2, 'Late Deduction', 180.00, 0),
    (@payslip_id, 2, 'Undertime Deduction', 450.00, 0),
    (@payslip_id, 2, 'Absence Deduction', 750.00, 0)
ON DUPLICATE KEY UPDATE
    component_type = VALUES(component_type),
    description = VALUES(description),
    amount = VALUES(amount),
    is_taxable = VALUES(is_taxable);

COMMIT;
