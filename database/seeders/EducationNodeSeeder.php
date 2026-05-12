<?php

namespace Database\Seeders;

use App\Models\EducationNode;
use Illuminate\Database\Seeder;

class EducationNodeSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: clear & reseed.
        EducationNode::query()->delete();

        $tree = [
            [
                'name' => 'Basic Education', 'type' => 'level',
                'children' => [
                    [
                        'name' => 'Elementary', 'type' => 'stage',
                        'children' => [
                            ['name' => 'Kindergarten', 'type' => 'stage'],
                            ['name' => 'Grade 1', 'type' => 'stage'],
                            ['name' => 'Grade 2', 'type' => 'stage'],
                            ['name' => 'Grade 3', 'type' => 'stage'],
                            ['name' => 'Grade 4', 'type' => 'stage'],
                            ['name' => 'Grade 5', 'type' => 'stage'],
                            ['name' => 'Grade 6', 'type' => 'stage'],
                        ],
                    ],
                    [
                        'name' => 'Junior High', 'type' => 'stage',
                        'children' => [
                            ['name' => 'Grade 7',  'type' => 'stage'],
                            ['name' => 'Grade 8',  'type' => 'stage'],
                            ['name' => 'Grade 9',  'type' => 'stage'],
                            ['name' => 'Grade 10', 'type' => 'stage'],
                        ],
                    ],
                    [
                        'name' => 'Senior High School', 'type' => 'stage',
                        'children' => [
                            [
                                'name' => 'Academic Track', 'type' => 'track',
                                'children' => [
                                    ['name' => 'STEM',  'type' => 'strand'],
                                    ['name' => 'ABM',   'type' => 'strand'],
                                    ['name' => 'HUMSS', 'type' => 'strand'],
                                    ['name' => 'GAS',   'type' => 'strand'],
                                ],
                            ],
                            [
                                'name' => 'TVL Track', 'type' => 'track',
                                'children' => [
                                    ['name' => 'ICT',               'type' => 'strand'],
                                    ['name' => 'Home Economics',    'type' => 'strand'],
                                    ['name' => 'Industrial Arts',   'type' => 'strand'],
                                    ['name' => 'Agri-Fishery Arts', 'type' => 'strand'],
                                ],
                            ],
                            ['name' => 'Sports Track',        'type' => 'track'],
                            ['name' => 'Arts & Design Track', 'type' => 'track'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Undergraduate', 'type' => 'level',
            ],
            [
                'name' => 'Post-Baccalaureate', 'type' => 'level',
                'children' => [
                    ['name' => 'Certificate in Teaching', 'type' => 'program_type'],
                    ['name' => 'Post-Bacc Diploma',       'type' => 'program_type'],
                ],
            ],
            [
                'name' => 'Graduate Programs', 'type' => 'level',
                'children' => [
                    ['name' => 'Master’s Degree',   'type' => 'program_type'],
                    ['name' => 'Graduate Diploma', 'type' => 'program_type'],
                    ['name' => 'Doctoral Degree',  'type' => 'program_type'],
                ],
            ],
            [
                'name' => 'Post-Doctoral', 'type' => 'level',
                'children' => [
                    ['name' => 'Research Fellowships', 'type' => 'program_type'],
                    ['name' => 'Advanced Studies',     'type' => 'program_type'],
                ],
            ],
        ];

        $this->insertNodes($tree, null);
    }

    private function insertNodes(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $i => $node) {
            $created = EducationNode::create([
                'name'        => $node['name'],
                'parent_id'   => $parentId,
                'node_type'   => $node['type'],
                'order_index' => $i,
                'is_offered'  => false,
                'is_active'   => true,
            ]);

            if (! empty($node['children'])) {
                $this->insertNodes($node['children'], $created->id);
            }
        }
    }
}
