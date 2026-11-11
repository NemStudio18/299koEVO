<?php

namespace Faq\Entities;

defined('ROOT') or exit('No direct script access allowed');

class FaqManager
{
    public function __construct()
    {
    }

    public function getQuestions()
    {
        return FaqQuestion::query()->get();
    }

    public function getActiveQuestions()
    {
        return FaqQuestion::query()->where('active', '=', '1')->get();
    }

    public function getQuestionsByCategory($category = '')
    {
        $query = FaqQuestion::query()->where('active', '=', '1');
        
        if (!empty($category)) {
            $query->where('category', '=', $category);
        }

        return $query->orderBy('order', 'ASC')->orderBy('votes', 'DESC')->get();
    }

    public function getCategories()
    {
        $questions = $this->getActiveQuestions();
        $categories = [];
        foreach ($questions as $q) {
            $cat = $q->getCategory();
            if (!empty($cat) && !in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
        sort($categories);
        return $categories;
    }

    public function create($id = null)
    {
        if ($id === null) {
            return new FaqQuestion();
        }
        return FaqQuestion::findPK($id);
    }

    public function saveQuestion($question)
    {
        if ($question->getId() === null) {
            $question->setDate(date('Y-m-d H:i:s'));
        }
        return $question->save();
    }

    public function deleteQuestion($id)
    {
        $question = FaqQuestion::findPK($id);
        if ($question) {
            return $question->delete();
        }
        return false;
    }
}



