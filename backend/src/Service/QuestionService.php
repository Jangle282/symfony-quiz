<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Difficulty;
use App\Entity\Question;
use App\Entity\Round;
use Doctrine\ORM\EntityManagerInterface;

class QuestionService
{
    public function __construct(
        private readonly QuestionProviderInterface $provider,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param Round[] $rounds
     */
    public function createQuestionsAndAnswers(array $rounds, Difficulty $difficulty, int $questionsPerRound = 10): void
    {
        foreach ($rounds as $round) {
            $questionDTOs = $this->provider->fetchQuestions($round, $difficulty, $questionsPerRound);

            foreach ($questionDTOs as $questionDTO) {
                $question = new Question();
                $question->setRound($round);
                $question->setQuestionText($questionDTO->questionText);
                $this->entityManager->persist($question);

                foreach ($questionDTO->answers as $answerDTO) {
                    $answer = new Answer();
                    $answer->setQuestion($question);
                    $answer->setAnswerText($answerDTO->answerText);
                    $answer->setIsCorrect($answerDTO->isCorrect);
                    $answer->setUserSelected(false);
                    $this->entityManager->persist($answer);

                    $question->addAnswer($answer);
                }

                $round->addQuestion($question);
            }
        }

        $this->entityManager->flush();
    }
}
