<?php

namespace Database\Seeders\ProfessionalEducation;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentOfLearningSeeder extends Seeder
{
    public function run()
    {
        $schoolId = 1;

        // =========================
        // SUBJECT (ensure exists)
        // =========================
        $subjectId = DB::table('subjects')->where('code', 'PROF-ED')->value('id');

        if (!$subjectId) {
            $subjectId = DB::table('subjects')->insertGetId([
                'school_id' => $schoolId,
                'code' => 'PROF-ED',
                'name' => 'Professional Education',
                'description' => 'LET Professional Education',
                'is_active' => 1,
                'scope' => 'training',
                'created_at' => now()
            ]);
        }

        // =========================
        // TOPIC: Assessment of Learning
        // =========================
        $topicId = DB::table('topics')->insertGetId([
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'name' => 'Assessment of Learning',
            'code' => 'AOL',
            'sequence' => 5,
            'is_active' => 1,
            'created_at' => now()
        ]);

        /*
        ============================================
        LESSON 1: Nature & Purpose of Assessment
        ============================================
        */
        $lesson1 = DB::table('lessons')->insertGetId([
            'school_id'=>$schoolId,
            'subject_id'=>$subjectId,
            'topic_id'=>$topicId,
            'name'=>'Nature and Purpose of Assessment',
            'code'=>'AOL-NATURE',
            'sequence'=>1,
            'is_active'=>1,
            'created_at'=>now()
        ]);

        DB::table('competencies')->insert([
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson1,'name'=>'Define assessment, measurement, and evaluation','bloom_level'=>'remember','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson1,'name'=>'Differentiate assessment from evaluation','bloom_level'=>'understand','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson1,'name'=>'Explain purposes of assessment in teaching','bloom_level'=>'understand','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson1,'name'=>'Apply assessment to improve instruction','bloom_level'=>'apply','mastery_threshold'=>75],
        ]);

        /*
        ============================================
        LESSON 2: Types of Assessment
        ============================================
        */
        $lesson2 = DB::table('lessons')->insertGetId([
            'school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,
            'name'=>'Types of Assessment','code'=>'AOL-TYPES',
            'sequence'=>2,'is_active'=>1,'created_at'=>now()
        ]);

        DB::table('competencies')->insert([
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson2,'name'=>'Differentiate formative and summative assessment','bloom_level'=>'understand','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson2,'name'=>'Identify diagnostic assessment','bloom_level'=>'remember','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson2,'name'=>'Select appropriate type of assessment for objectives','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson2,'name'=>'Analyze assessment scenarios','bloom_level'=>'analyze','mastery_threshold'=>75],
        ]);

        /*
        ============================================
        LESSON 3: Test Construction
        ============================================
        */
        $lesson3 = DB::table('lessons')->insertGetId([
            'school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,
            'name'=>'Test Construction','code'=>'AOL-TEST',
            'sequence'=>3,'is_active'=>1,'created_at'=>now()
        ]);

        DB::table('competencies')->insert([
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson3,'name'=>'Construct multiple choice questions','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson3,'name'=>'Construct essay and situational questions','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson3,'name'=>'Identify characteristics of good test items','bloom_level'=>'understand','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson3,'name'=>'Analyze validity and reliability of tests','bloom_level'=>'analyze','mastery_threshold'=>75],
        ]);

        /*
        ============================================
        LESSON 4: Table of Specifications (TOS)
        ============================================
        */
        $lesson4 = DB::table('lessons')->insertGetId([
            'school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,
            'name'=>'Table of Specifications','code'=>'AOL-TOS',
            'sequence'=>4,'is_active'=>1,'created_at'=>now()
        ]);

        DB::table('competencies')->insert([
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson4,'name'=>'Construct a table of specifications','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson4,'name'=>'Align test items with objectives','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson4,'name'=>'Interpret TOS data','bloom_level'=>'analyze','mastery_threshold'=>75],
        ]);

        /*
        ============================================
        LESSON 5: Item Analysis
        ============================================
        */
        $lesson5 = DB::table('lessons')->insertGetId([
            'school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,
            'name'=>'Item Analysis','code'=>'AOL-ITEM',
            'sequence'=>5,'is_active'=>1,'created_at'=>now()
        ]);

        DB::table('competencies')->insert([
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson5,'name'=>'Compute difficulty index','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson5,'name'=>'Compute discrimination index','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson5,'name'=>'Interpret item analysis results','bloom_level'=>'analyze','mastery_threshold'=>75],
        ]);

        /*
        ============================================
        LESSON 6: Alternative Assessment
        ============================================
        */
        $lesson6 = DB::table('lessons')->insertGetId([
            'school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,
            'name'=>'Alternative Assessment','code'=>'AOL-ALT',
            'sequence'=>6,'is_active'=>1,'created_at'=>now()
        ]);

        DB::table('competencies')->insert([
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson6,'name'=>'Differentiate traditional and authentic assessment','bloom_level'=>'understand','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson6,'name'=>'Design performance-based assessment','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson6,'name'=>'Develop rubrics for scoring','bloom_level'=>'apply','mastery_threshold'=>75],
        ]);

        /*
        ============================================
        LESSON 7: Grading and Reporting
        ============================================
        */
        $lesson7 = DB::table('lessons')->insertGetId([
            'school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,
            'name'=>'Grading and Reporting','code'=>'AOL-GRADE',
            'sequence'=>7,'is_active'=>1,'created_at'=>now()
        ]);

        DB::table('competencies')->insert([
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson7,'name'=>'Compute grades using different methods','bloom_level'=>'apply','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson7,'name'=>'Interpret grading systems','bloom_level'=>'understand','mastery_threshold'=>75],
            ['school_id'=>$schoolId,'subject_id'=>$subjectId,'topic_id'=>$topicId,'lesson_id'=>$lesson7,'name'=>'Communicate assessment results effectively','bloom_level'=>'apply','mastery_threshold'=>75],
        ]);
    }
}