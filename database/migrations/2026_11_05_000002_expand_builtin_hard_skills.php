<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $skills = [
            'Microsoft Office', 'Microsoft Outlook', 'Google Sheets', 'Google Docs', 'Google Slides',
            'Digital Literacy', 'AI Literacy', 'Cybersecurity Awareness', 'Data Entry',
            'Spreadsheet Management', 'Data Visualisation', 'Business Analysis', 'Business Writing',
            'Report Writing', 'Presentation Development', 'Budgeting', 'Financial Reporting',
            'Customer Relationship Management (CRM)', 'Sales Operations', 'Social Media Management',
            'Content Management', 'Inventory Management', 'Procurement', 'Supply Chain Coordination',
            'Quality Assurance', 'Records Management', 'Microsoft Power BI', 'Business Process Improvement',
        ];

        foreach ($skills as $name) {
            $exists = DB::table('skills')->where('type', 'hard')->where('name', $name)->where('is_builtin', true)->exists();
            if (! $exists) {
                DB::table('skills')->insert([
                    'name' => $name,
                    'type' => 'hard',
                    'created_by_job_seeker_id' => null,
                    'is_builtin' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('skills')->where('type', 'hard')->where('is_builtin', true)->whereIn('name', [
            'Microsoft Office', 'Microsoft Outlook', 'Google Sheets', 'Google Docs', 'Google Slides',
            'Digital Literacy', 'AI Literacy', 'Cybersecurity Awareness', 'Data Entry',
            'Spreadsheet Management', 'Data Visualisation', 'Business Analysis', 'Business Writing',
            'Report Writing', 'Presentation Development', 'Budgeting', 'Financial Reporting',
            'Customer Relationship Management (CRM)', 'Sales Operations', 'Social Media Management',
            'Content Management', 'Inventory Management', 'Procurement', 'Supply Chain Coordination',
            'Quality Assurance', 'Records Management', 'Microsoft Power BI', 'Business Process Improvement',
        ])->update(['is_active' => false]);
    }
};
