<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkMode;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use \Illuminate\Database\Console\Seeds\WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@recruitx.test'],
            ['name' => 'Admin User', 'password' => 'password']
        );
        $admin->assignRole('admin');

        $recruiter = User::firstOrCreate(
            ['email' => 'recruiter@recruitx.test'],
            ['name' => 'Recruiter User', 'password' => 'password']
        );
        $recruiter->assignRole('recruiter');

        $candidateUser = User::firstOrCreate(
            ['email' => 'candidate@recruitx.test'],
            ['name' => 'Candidate User', 'password' => 'password']
        );
        $candidateUser->assignRole('candidate');

        Candidate::firstOrCreate(
            ['user_id' => $candidateUser->id],
            [
                'first_name' => 'Candidate',
                'last_name' => 'User',
                'city' => 'Sfax',
                'country' => 'Tunisia',
            ]
        );

        $skills = [
            ['name' => 'Laravel', 'category' => 'Backend'],
            ['name' => 'PHP', 'category' => 'Backend'],
            ['name' => 'React', 'category' => 'Frontend'],
            ['name' => 'TypeScript', 'category' => 'Frontend'],
            ['name' => 'Vue', 'category' => 'Frontend'],
            ['name' => 'Angular', 'category' => 'Frontend'],
            ['name' => 'Java', 'category' => 'Backend'],
            ['name' => 'Spring Boot', 'category' => 'Backend'],
            ['name' => 'MySQL', 'category' => 'Database'],
            ['name' => 'PostgreSQL', 'category' => 'Database'],
            ['name' => 'Redis', 'category' => 'Database'],
            ['name' => 'Docker', 'category' => 'DevOps'],
            ['name' => 'Git', 'category' => 'DevOps'],
            ['name' => 'AWS', 'category' => 'DevOps'],
            ['name' => 'Python', 'category' => 'Backend'],
            ['name' => 'Node.js', 'category' => 'Backend'],
            ['name' => 'Tailwind CSS', 'category' => 'Frontend'],
            ['name' => 'Figma', 'category' => 'Design'],
            ['name' => 'Go', 'category' => 'Backend'],
            ['name' => 'C#', 'category' => 'Backend'],
        ];

        foreach ($skills as $skill) {
            Skill::firstOrCreate(['name' => $skill['name']], $skill);
        }

        $techNova = Company::firstOrCreate(
            ['name' => 'TechNova'],
            [
                'description' => 'Software development company based in Tunis.',
                'website' => 'https://technova.example',
                'city' => 'Tunis',
                'country' => 'Tunisia',
                'industry' => 'Software',
                'size' => '50-200',
            ]
        );

        $dataPeak = Company::firstOrCreate(
            ['name' => 'DataPeak'],
            [
                'description' => 'Data analytics and AI solutions.',
                'website' => 'https://datapeak.example',
                'city' => 'Sfax',
                'country' => 'Tunisia',
                'industry' => 'Data',
                'size' => '10-50',
            ]
        );

        $laravel = Skill::where('name', 'Laravel')->first();
        $react = Skill::where('name', 'React')->first();
        $docker = Skill::where('name', 'Docker')->first();
        $mysql = Skill::where('name', 'MySQL')->first();
        $php = Skill::where('name', 'PHP')->first();

        $laravelJob = JobOffer::firstOrCreate(
            ['slug' => 'laravel-developer-technova'],
            [
                'company_id' => $techNova->id,
                'created_by' => $recruiter->id,
                'title' => 'Laravel Developer',
                'description' => 'Build and maintain REST APIs and web applications with Laravel.',
                'requirements' => '2+ years of PHP/Laravel experience.',
                'employment_type' => EmploymentType::FULL_TIME->value,
                'work_mode' => WorkMode::HYBRID->value,
                'location' => 'Tunis',
                'salary_min' => 2500,
                'salary_max' => 4000,
                'currency' => 'TND',
                'experience_min' => 2,
                'experience_max' => 5,
                'status' => JobStatus::PUBLISHED->value,
                'published_at' => now(),
            ]
        );
        $laravelJob->skills()->sync([
            $laravel->id => ['required_level' => 'Advanced', 'is_required' => true],
            $php->id => ['required_level' => 'Advanced', 'is_required' => true],
            $mysql->id => ['required_level' => 'Intermediate', 'is_required' => true],
            $docker->id => ['required_level' => 'Beginner', 'is_required' => false],
        ]);

        $reactJob = JobOffer::firstOrCreate(
            ['slug' => 'react-frontend-developer-datapeak'],
            [
                'company_id' => $dataPeak->id,
                'created_by' => $recruiter->id,
                'title' => 'React Frontend Developer',
                'description' => 'Build modern SPAs with React and TypeScript.',
                'requirements' => '2+ years of React experience.',
                'employment_type' => EmploymentType::FULL_TIME->value,
                'work_mode' => WorkMode::REMOTE->value,
                'location' => 'Remote',
                'salary_min' => 2200,
                'salary_max' => 3500,
                'currency' => 'TND',
                'experience_min' => 1,
                'experience_max' => 4,
                'status' => JobStatus::PUBLISHED->value,
                'published_at' => now(),
            ]
        );
        $reactJob->skills()->sync([
            $react->id => ['required_level' => 'Advanced', 'is_required' => true],
            $docker->id => ['required_level' => 'Beginner', 'is_required' => false],
        ]);
    }
}