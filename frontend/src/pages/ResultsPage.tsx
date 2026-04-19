import { useParams } from 'react-router-dom';

export default function ResultsPage() {
  const { id } = useParams<{ id: string }>();

  return (
    <div className="container mx-auto p-8">
      <h1 className="text-3xl font-bold">Results</h1>
      <p className="text-base-content/60 mt-2">Results display coming in Phase 11</p>
      <p className="text-sm text-base-content/40 mt-1">Game ID: {id}</p>
    </div>
  );
}
