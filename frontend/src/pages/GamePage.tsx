import { useCallback, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import axios from 'axios';
import { getGame, completeGame } from '../api/gameService';
import { getNextQuestion, getPreviousQuestion } from '../api/questionService';
import { selectAnswer } from '../api/answerService';
import type { GameDetailsResponse, Question, RoundSummary } from '../types';
import GameHeader from '../components/game/GameHeader';
import QuestionCard from '../components/game/QuestionCard';
import QuestionNavigation from '../components/game/QuestionNavigation';

export default function GamePage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const [game, setGame] = useState<GameDetailsResponse | null>(null);
  const [currentQuestion, setCurrentQuestion] = useState<Question | null>(null);
  const [currentRound, setCurrentRound] = useState<RoundSummary | null>(null);
  const [questionNumber, setQuestionNumber] = useState(1);

  const [selectedAnswerId, setSelectedAnswerId] = useState<string | null>(null);
  const [submittedAnswerId, setSubmittedAnswerId] = useState<string | null>(null);

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [navigating, setNavigating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const initFromQuestion = useCallback((question: Question | null) => {
    setCurrentQuestion(question);
    if (question) {
      const submitted = question.answers.find((a) => a.user_selected);
      const submittedId = submitted?.id ?? null;
      setSubmittedAnswerId(submittedId);
      setSelectedAnswerId(submittedId);
    } else {
      setSubmittedAnswerId(null);
      setSelectedAnswerId(null);
    }
  }, []);

  useEffect(() => {
    if (!id) return;

    async function loadGame() {
      setLoading(true);
      setError(null);
      try {
        const data = await getGame(id!);
        setGame(data);

        if (data.completed_at) {
          navigate(`/results/${id}`, { replace: true });
          return;
        }

        const round = data.rounds[0] ?? null;
        setCurrentRound(round);
        initFromQuestion(data.current_question);

        if (round) {
          setQuestionNumber(
            data.current_question
              ? round.answered_questions + 1
              : round.answered_questions + 1,
          );
        }
      } catch (err) {
        if (axios.isAxiosError(err) && err.response?.data?.error) {
          setError(err.response.data.error);
        } else {
          setError('Failed to load game.');
        }
      } finally {
        setLoading(false);
      }
    }

    loadGame();
  }, [id, navigate, initFromQuestion]);

  function handleSelectAnswer(answerId: string) {
    setSelectedAnswerId(answerId);
  }

  async function handleSubmitAnswer() {
    if (!id || !currentRound || !currentQuestion || !selectedAnswerId) return;

    setSubmitting(true);
    setError(null);
    try {
      await selectAnswer(id, currentRound.id, currentQuestion.id, selectedAnswerId);
      setSubmittedAnswerId(selectedAnswerId);
    } catch (err) {
      if (axios.isAxiosError(err) && err.response?.data?.error) {
        setError(err.response.data.error);
      } else {
        setError('Failed to submit answer.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  async function handleNext() {
    if (!id || !currentRound || !currentQuestion) return;

    setNavigating(true);
    setError(null);
    try {
      const data = await getNextQuestion(id, currentRound.id, currentQuestion.id);
      if (data.question) {
        initFromQuestion(data.question);
        setQuestionNumber((n) => n + 1);
      }
    } catch (err) {
      if (axios.isAxiosError(err) && err.response?.data?.error) {
        setError(err.response.data.error);
      } else {
        setError('Failed to load next question.');
      }
    } finally {
      setNavigating(false);
    }
  }

  async function handlePrevious() {
    if (!id || !currentRound || !currentQuestion) return;

    setNavigating(true);
    setError(null);
    try {
      const data = await getPreviousQuestion(id, currentRound.id, currentQuestion.id);
      if (data.question) {
        initFromQuestion(data.question);
        setQuestionNumber((n) => n - 1);
      }
    } catch (err) {
      if (axios.isAxiosError(err) && err.response?.data?.error) {
        setError(err.response.data.error);
      } else {
        setError('Failed to load previous question.');
      }
    } finally {
      setNavigating(false);
    }
  }

  async function handleViewResults() {
    if (!id) return;

    setNavigating(true);
    setError(null);
    try {
      await completeGame(id);
      navigate(`/results/${id}`);
    } catch (err) {
      if (axios.isAxiosError(err) && err.response?.data?.error) {
        setError(err.response.data.error);
      } else {
        setError('Failed to complete game.');
      }
    } finally {
      setNavigating(false);
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-[50vh]" data-cy="game-loading">
        <span className="loading loading-spinner loading-lg" />
      </div>
    );
  }

  if (error && !game) {
    return (
      <div className="container mx-auto p-8 max-w-2xl">
        <div className="alert alert-error" data-cy="game-error">
          <span>{error}</span>
        </div>
      </div>
    );
  }

  if (!game || !currentRound || !currentQuestion) {
    return (
      <div className="container mx-auto p-8 max-w-2xl">
        <div className="alert alert-warning">
          <span>No questions available for this game.</span>
        </div>
      </div>
    );
  }

  const totalQuestions = currentRound.total_questions;
  const isLastQuestion = questionNumber >= totalQuestions;
  const needsSubmit = selectedAnswerId !== submittedAnswerId;

  return (
    <div className="container mx-auto p-8 max-w-2xl" data-cy="game-page">
      <GameHeader
        name={game.name}
        difficulty={game.difficulty}
        roundNumber={currentRound.round_number}
        category={currentRound.category}
        questionNumber={questionNumber}
        totalQuestions={totalQuestions}
      />

      <QuestionCard
        question={currentQuestion}
        selectedAnswerId={selectedAnswerId}
        submittedAnswerId={submittedAnswerId}
        onSelectAnswer={handleSelectAnswer}
        disabled={submitting || navigating}
      />

      {error && (
        <div className="alert alert-error mb-4" data-cy="game-action-error">
          <span>{error}</span>
        </div>
      )}

      <QuestionNavigation
        onPrevious={handlePrevious}
        onNext={handleNext}
        onSubmit={handleSubmitAnswer}
        onViewResults={handleViewResults}
        hasPrevious={questionNumber > 1}
        isLastQuestion={isLastQuestion}
        hasSubmittedAnswer={submittedAnswerId !== null}
        hasSelectedAnswer={selectedAnswerId !== null}
        isSubmitting={submitting}
        isNavigating={navigating}
        needsSubmit={needsSubmit}
      />
    </div>
  );
}
