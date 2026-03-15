import { useMemo } from 'react'
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query'
import { fetchHealth } from './api/health'
import './App.css'

function HealthStatus() {
  const { data, isLoading, isError } = useQuery(['health'], fetchHealth, {
    retry: false,
    refetchOnWindowFocus: false,
  })

  if (isLoading) return <p>Checking API status…</p>
  if (isError) return <p style={{ color: 'var(--danger)' }}>API unreachable</p>

  return (
    <div>
      <p>API status: <strong>{data.status}</strong></p>
      <p>Timestamp: {data.timestamp}</p>
    </div>
  )
}

function App() {
  const queryClient = useMemo(() => new QueryClient(), [])

  return (
    <QueryClientProvider client={queryClient}>
      <main className="app">
        <header>
          <h1>Pub Quiz</h1>
          <p>React + Symfony starter</p>
        </header>
        <section>
          <h2>Backend health</h2>
          <HealthStatus />
        </section>
      </main>
    </QueryClientProvider>
  )
}

export default App
