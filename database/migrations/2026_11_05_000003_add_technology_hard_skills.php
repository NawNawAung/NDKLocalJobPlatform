<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $skillGroups = [
        'Programming and Web Development' => [
            'C', 'C++', 'C#', 'Java', 'JavaScript', 'TypeScript', 'Python', 'PHP', 'Ruby', 'Go', 'Rust',
            'Swift', 'Kotlin', 'Dart', 'R Programming', 'MATLAB', 'Bash', 'PowerShell', 'HTML', 'CSS',
            'Node.js', 'Express.js', 'React', 'Angular', 'Vue.js', 'Next.js', 'Nuxt.js', 'Svelte',
            'Laravel', 'Symfony', 'Django', 'Flask', 'FastAPI', 'Spring Boot', '.NET', 'ASP.NET Core',
            'REST API Development', 'GraphQL', 'Microservices', 'Software Testing', 'Automated Testing',
            'Test-Driven Development', 'Web Accessibility',
        ],
        'DevOps, Cloud and Systems' => [
            'DevOps', 'Site Reliability Engineering (SRE)', 'Platform Engineering', 'Git', 'GitHub',
            'GitLab', 'CI/CD', 'Docker', 'Kubernetes', 'Terraform', 'Ansible', 'Jenkins',
            'GitHub Actions', 'GitLab CI', 'Amazon Web Services (AWS)', 'Microsoft Azure',
            'Google Cloud Platform (GCP)', 'Linux System Administration', 'Windows Server Administration',
            'Nginx', 'Apache HTTP Server', 'Virtualisation', 'VMware vSphere', 'Microsoft Hyper-V',
            'Prometheus', 'Grafana', 'ELK Stack', 'Configuration Management', 'Shell Scripting',
            'Backup and Disaster Recovery', 'Server Administration', 'Cloud Architecture',
        ],
        'Networking and Telecommunications' => [
            'Computer Networking', 'TCP/IP', 'DNS', 'DHCP', 'LAN/WAN', 'VLAN', 'Routing and Switching',
            'Cisco IOS', 'Cisco Networking', 'Juniper Junos', 'Wireless Networking', 'Wi-Fi Administration',
            'Virtual Private Networks (VPN)', 'Firewall Administration', 'Network Monitoring',
            'Network Troubleshooting', 'Load Balancing', 'BGP Routing', 'Network Cabling',
            'Telecommunications Systems', 'VoIP', 'SD-WAN', 'Network Design',
        ],
        'Cybersecurity' => [
            'Cybersecurity', 'Security Operations Centre (SOC)', 'Security Information and Event Management (SIEM)',
            'Incident Response', 'Vulnerability Management', 'Penetration Testing', 'Identity and Access Management (IAM)',
            'Endpoint Security', 'Network Security', 'Cloud Security', 'Encryption', 'Zero Trust Security',
            'Security Auditing', 'ISO 27001', 'Digital Forensics',
        ],
        'Hardware, Electronics and Embedded Systems' => [
            'Computer Hardware Troubleshooting', 'PC Assembly and Repair', 'Hardware Diagnostics',
            'Hardware Maintenance', 'Data Centre Operations', 'Electronics', 'Embedded Systems',
            'Microcontrollers', 'Arduino', 'Raspberry Pi', 'Internet of Things (IoT)', 'PLC Programming',
            'SCADA Systems', 'Industrial Automation', 'Robotics', 'Firmware Development', 'PCB Design',
            'Computer-Aided Design (CAD)', 'AutoCAD', 'SolidWorks',
        ],
        'Data, Databases and Artificial Intelligence' => [
            'PostgreSQL', 'MySQL', 'Microsoft SQL Server', 'Oracle Database', 'MongoDB', 'Redis',
            'Database Administration', 'Database Design', 'Data Modelling', 'Data Warehousing', 'ETL',
            'Apache Spark', 'Hadoop', 'Pandas', 'NumPy', 'Jupyter', 'Tableau', 'Microsoft Power BI',
            'Looker', 'Machine Learning', 'Deep Learning', 'Natural Language Processing (NLP)',
            'Computer Vision', 'Generative AI', 'Prompt Engineering', 'MLOps', 'Statistical Analysis',
            'Data Visualisation',
        ],
        'Design and Digital Content' => [
            'Figma', 'Adobe Photoshop', 'Adobe Illustrator', 'Adobe XD', 'Blender', '3D Modelling',
            'UI Design', 'UX Research', 'Wireframing', 'Prototyping', 'Video Editing', 'Content Management Systems (CMS)',
        ],
        'Enterprise and Business Technology' => [
            'SAP', 'Oracle ERP', 'Microsoft Dynamics 365', 'Salesforce', 'HubSpot', 'ServiceNow', 'Jira',
            'Confluence', 'Microsoft SharePoint', 'Microsoft 365 Administration', 'Enterprise Resource Planning (ERP)',
            'Customer Relationship Management (CRM)', 'IT Service Management (ITSM)', 'Technical Support',
            'Help Desk Support', 'IT Asset Management', 'Business Process Automation',
        ],
    ];

    public function up(): void
    {
        foreach ($this->skillGroups as $names) {
            foreach ($names as $name) {
                $exists = DB::table('skills')->where('type', 'hard')->where('name', $name)->where('is_builtin', true)->exists();
                if ($exists) {
                    continue;
                }

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
        // Preserve selectable catalogue data during rollback; existing profiles may reference these rows.
    }
};
