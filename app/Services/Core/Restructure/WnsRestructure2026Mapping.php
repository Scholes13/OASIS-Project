<?php

namespace App\Services\Core\Restructure;

/**
 * Static mapping of users to their target (BU, dept, position) under the
 * WNS Restructure 2026 plan.
 *
 * Source: docs/specs/2026-05-25-wns-restructure-prd/04-data-migration-plan.md.
 *
 * Format per row:
 *   ['email', 'business_unit_code', 'department_code', 'position_code']
 *
 * Department and Position codes must already exist in DB before running
 * the migration command. Order: BusinessUnitSeeder -> WNSDepartmentSeeder
 * -> WNSSalesMarketingSeeder -> WNSSalesMarketingPositionSeeder
 * -> WNSKayanaUserSeeder -> migrate command.
 *
 * Pending entries (TBD email) are commented out and must be filled in
 * after running the verification tinker query in section 04.
 */
class WnsRestructure2026Mapping
{
    /**
     * @return array<int, array{email: string, bu: string, dept: string, position: string}>
     */
    public static function moves(): array
    {
        return [
            // WG / Executive Office (existing users from UserSeeder.php)
            ['email' => 'fadli@werkudara.com', 'bu' => 'WG', 'dept' => 'EXEC', 'position' => 'CEO_EXEC'],
            ['email' => 'bagus@werkudara.com', 'bu' => 'WG', 'dept' => 'EXEC', 'position' => 'MD_EXEC'],

            // WNS / Executive Office: Chief of Staff (PO 2026-05-26 revision —
            // Adiel sits at WNS/EXEC, not WG/EXEC, with executive-level
            // visibility into all WNS data).
            ['email' => 'adiel@werkudara.com', 'bu' => 'WNS', 'dept' => 'EXEC', 'position' => 'COS_EXEC'],

            // WNS / SM (root): General Manager + Asisten GM
            ['email' => 'andri@werkudara.com', 'bu' => 'WNS', 'dept' => 'SM', 'position' => 'GM_SM'],
            ['email' => 'ainur@werkudara.com', 'bu' => 'WNS', 'dept' => 'SM', 'position' => 'ASGM_SM'],

            // WNS / SO: new coordinator role (existing user, position change)
            ['email' => 'gilang@werkudara.com', 'bu' => 'WNS', 'dept' => 'SO', 'position' => 'COORD_SO'],

            // WNS / SM / BS: Business Solutions Division
            ['email' => 'irvani@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'MGR_BS'],
            ['email' => 'kensrie@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'MGR_BS'],
            ['email' => 'emy@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'MGR_BS'],
            ['email' => 'nindy@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'MGR_BS'],
            ['email' => 'mya@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'MGR_BS'],
            ['email' => 'mitha@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'MGR_BS'],
            ['email' => 'tsania@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'ENG_BS'],
            ['email' => 'enggar@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'ENG_BS'],
            ['email' => 'elfasa@werkudara.com', 'bu' => 'WNS', 'dept' => 'BS', 'position' => 'SPEC_BS'],

            // WNS / SM / COM: Commercial Division
            ['email' => 'linda@werkudara.com', 'bu' => 'WNS', 'dept' => 'COM', 'position' => 'MGR_COM'],
            ['email' => 'vanessa@werkudara.com', 'bu' => 'WNS', 'dept' => 'COM', 'position' => 'ANL_COM'],
            ['email' => 'haekal@werkudara.com', 'bu' => 'WNS', 'dept' => 'COM', 'position' => 'ANL_COM'],
            ['email' => 'refangga@werkudara.com', 'bu' => 'WNS', 'dept' => 'COM', 'position' => 'DSN_COM'],

            // WNS / SM / CMC: Corporate Marketing Communication Division
            ['email' => 'jaka@werkudara.com', 'bu' => 'WNS', 'dept' => 'CMC', 'position' => 'LEAD_CMC'],
            ['email' => 'septian@werkudara.com', 'bu' => 'WNS', 'dept' => 'CMC', 'position' => 'STG_CMC'],
            ['email' => 'andrew@werkudara.com', 'bu' => 'WNS', 'dept' => 'CMC', 'position' => 'DSN_CMC'],
            ['email' => 'abhi@werkudara.com', 'bu' => 'WNS', 'dept' => 'CMC', 'position' => 'ANL_CMC'],
        ];
    }
}
