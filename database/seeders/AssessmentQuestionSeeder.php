<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssessmentType;
use App\Models\AssessmentQuestion;
use Illuminate\Support\Str;

class AssessmentQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Technical Test' => [
                [
                    'question' => 'What does HTML stand for?',
                    'choices' => [
                        'HyperText Markup Language',
                        'HighText Machine Language',
                        'HyperTool Multi Language',
                        'Home Tool Markup Language',
                    ],
                    'answer' => 0,
                ],
                [
                    'question' => 'Which SQL command is used to retrieve data?',
                    'choices' => [
                        'INSERT',
                        'SELECT',
                        'UPDATE',
                        'DELETE',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => 'In Laravel, which folder usually contains controllers?',
                    'choices' => [
                        'resources/views',
                        'database/migrations',
                        'app/Http/Controllers',
                        'public/assets',
                    ],
                    'answer' => 2,
                ],
                [
                    'question' => 'Which HTTP method is commonly used to submit a form that creates a new record?',
                    'choices' => [
                        'GET',
                        'POST',
                        'PUT',
                        'DELETE',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => 'What is the purpose of validation in a system?',
                    'choices' => [
                        'To check and control user input',
                        'To delete all records',
                        'To slow down the system',
                        'To remove authentication',
                    ],
                    'answer' => 0,
                ],
            ],

            'Personality Test' => [
                [
                    'question' => 'How do you usually handle urgent tasks?',
                    'choices' => [
                        'I ignore them until later.',
                        'I prioritize them and communicate with the team.',
                        'I wait for others to decide.',
                        'I stop all other work permanently.',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => 'Which behavior best shows professionalism?',
                    'choices' => [
                        'Arriving late without notice.',
                        'Keeping commitments and communicating clearly.',
                        'Avoiding feedback.',
                        'Not following instructions.',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => 'When receiving feedback, what is the best response?',
                    'choices' => [
                        'Listen, clarify, and improve.',
                        'Ignore the feedback.',
                        'Argue immediately.',
                        'Blame someone else.',
                    ],
                    'answer' => 0,
                ],
                [
                    'question' => 'Which trait is important in a workplace?',
                    'choices' => [
                        'Accountability',
                        'Dishonesty',
                        'Carelessness',
                        'Avoiding teamwork',
                    ],
                    'answer' => 0,
                ],
                [
                    'question' => 'What should you do if you do not understand a task?',
                    'choices' => [
                        'Pretend you understand.',
                        'Ask for clarification.',
                        'Submit random work.',
                        'Delay without informing anyone.',
                    ],
                    'answer' => 1,
                ],
            ],

            'Aptitude Test' => [
                [
                    'question' => 'What is 15% of 200?',
                    'choices' => [
                        '15',
                        '20',
                        '30',
                        '45',
                    ],
                    'answer' => 2,
                ],
                [
                    'question' => 'If A is greater than B, and B is greater than C, which is true?',
                    'choices' => [
                        'C is greatest',
                        'A is greatest',
                        'B is greatest',
                        'All are equal',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => 'Complete the pattern: 2, 4, 8, 16, ___',
                    'choices' => [
                        '18',
                        '24',
                        '30',
                        '32',
                    ],
                    'answer' => 3,
                ],
                [
                    'question' => 'A task starts at 9:15 AM and ends at 10:45 AM. How long did it take?',
                    'choices' => [
                        '1 hour',
                        '1 hour 15 minutes',
                        '1 hour 30 minutes',
                        '2 hours',
                    ],
                    'answer' => 2,
                ],
                [
                    'question' => 'Which word is closest in meaning to "reliable"?',
                    'choices' => [
                        'Dependable',
                        'Careless',
                        'Late',
                        'Weak',
                    ],
                    'answer' => 0,
                ],
            ],
        ];

        foreach ($types as $typeName => $questions) {
            $type = AssessmentType::updateOrCreate(
                [
                    'name' => $typeName,
                ],
                [
                    'slug' => Str::slug($typeName),
                    'description' => $typeName . ' question set.',
                    'is_active' => true,
                ]
            );

            foreach ($questions as $index => $item) {
                AssessmentQuestion::updateOrCreate(
                    [
                        'assessment_type_id' => $type->id,
                        'question' => $item['question'],
                    ],
                    [
                        'choice_a' => $item['choices'][0],
                        'choice_b' => $item['choices'][1],
                        'choice_c' => $item['choices'][2],
                        'choice_d' => $item['choices'][3],
                        'correct_answer' => $item['answer'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
