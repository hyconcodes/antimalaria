<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <header class="border-b border-zinc-800">
            <nav class="mx-auto flex w-full max-w-7xl items-center justify-between gap-6 px-5 py-5 sm:px-8" aria-label="Main navigation">
                <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold tracking-tight">
                    <span class="grid size-10 place-items-center rounded-xl bg-indigo-400/10 text-sm font-bold text-indigo-300" aria-hidden="true">AM</span>
                    <span>Antimalaria <span class="text-zinc-400">Research</span></span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="#methodology" class="hidden rounded-lg px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-900 hover:text-white sm:inline-flex">Methodology</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium hover:border-zinc-500">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-900 hover:text-white">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-400 px-4 py-2 text-sm font-semibold text-zinc-950 hover:bg-indigo-300">Create account</a>
                        @endif
                    @endauth
                </div>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid w-full max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(20rem,0.8fr)] lg:items-center lg:py-28">
                <div class="flex flex-col items-start gap-7">
                    <span class="rounded-full border border-indigo-300/20 bg-indigo-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-200">Transparent · rule-based · versioned</span>
                    <div class="flex flex-col gap-5">
                        <h1 class="max-w-4xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">A transparent assessment of malaria resistance-associated markers.</h1>
                        <p class="max-w-2xl text-lg leading-8 text-zinc-300">
                            Record available <em>Plasmodium falciparum</em> marker calls and evaluate them against a documented, versioned rule catalogue for chloroquine resistance-associated genetic markers.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('assessments.create') }}" class="rounded-xl bg-indigo-400 px-5 py-3 text-sm font-semibold text-zinc-950 hover:bg-indigo-300">Start an assessment</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl bg-indigo-400 px-5 py-3 text-sm font-semibold text-zinc-950 hover:bg-indigo-300">Sign in to assess</a>
                        @endauth
                        <a href="#methodology" class="rounded-xl border border-zinc-700 px-5 py-3 text-sm font-semibold text-zinc-100 hover:border-zinc-500">Explore the methodology</a>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-zinc-400">For academic research, demonstration, and decision support only. Not a diagnostic system and not a treatment recommendation.</p>
                </div>

                <aside class="flex flex-col gap-5 rounded-3xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-2xl shadow-black/20 sm:p-8" aria-label="Assessment workflow">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-400">Assessment workflow</p>
                            <h2 class="mt-1 text-xl font-semibold">Evidence before interpretation</h2>
                        </div>
                        <span class="grid size-11 place-items-center rounded-xl bg-indigo-400/10 text-indigo-200" aria-hidden="true">01</span>
                    </div>
                    <ol class="flex flex-col gap-4">
                        <li class="flex gap-4 rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                            <span class="font-mono text-sm text-indigo-300">01</span>
                            <div><h3 class="font-medium">Record calls</h3><p class="mt-1 text-sm leading-6 text-zinc-400">Enter a pseudonymous sample reference and available marker calls, including unknown or untested states.</p></div>
                        </li>
                        <li class="flex gap-4 rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                            <span class="font-mono text-sm text-indigo-300">02</span>
                            <div><h3 class="font-medium">Evaluate explicit rules</h3><p class="mt-1 text-sm leading-6 text-zinc-400">Only approved, enabled rules with documented evidence are considered.</p></div>
                        </li>
                        <li class="flex gap-4 rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                            <span class="font-mono text-sm text-indigo-300">03</span>
                            <div><h3 class="font-medium">Review a reproducible record</h3><p class="mt-1 text-sm leading-6 text-zinc-400">The result retains the input, matched rule explanations, sources, and rule-set version used.</p></div>
                        </li>
                    </ol>
                </aside>
            </section>

            <section class="border-y border-zinc-800 bg-zinc-900/50">
                <div class="mx-auto grid w-full max-w-7xl gap-8 px-5 py-16 sm:px-8 md:grid-cols-3">
                    <article class="flex flex-col gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-300">Marker focus</span>
                        <h2 class="text-xl font-semibold">Calls, not conclusions</h2>
                        <p class="text-sm leading-6 text-zinc-400">The initial input catalogue covers selected PfCRT and PfMDR1 calls. Missing, mixed, uncallable, conflicting, or unsupported calls remain explicit.</p>
                    </article>
                    <article class="flex flex-col gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-300">Traceability</span>
                        <h2 class="text-xl font-semibold">Rules linked to evidence</h2>
                        <p class="text-sm leading-6 text-zinc-400">Each rule records its marker condition, rationale, evidence classification, source, review state, and revision. Unapproved rules never affect assessments.</p>
                    </article>
                    <article class="flex flex-col gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-300">Reproducibility</span>
                        <h2 class="text-xl font-semibold">Historical results stay fixed</h2>
                        <p class="text-sm leading-6 text-zinc-400">Saved assessments retain an input snapshot and the exact evaluated rule definitions, so later catalogue changes do not rewrite past results.</p>
                    </article>
                </div>
            </section>

            <section id="methodology" class="mx-auto grid w-full max-w-7xl gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.7fr)] lg:py-20">
                <div class="flex flex-col gap-5">
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-300">Methodology and limitations</span>
                    <h2 class="text-3xl font-semibold tracking-tight">Transparent rules do not replace scientific review.</h2>
                    <p class="max-w-3xl leading-7 text-zinc-300">The system compares submitted marker calls with a finite set of source-linked rules that an authorized reviewer has approved and enabled. It does not use machine learning, infer missing alleles, or treat absence of a marker as proof of susceptibility.</p>
                    <p class="max-w-3xl leading-7 text-zinc-300">The WHO compendium describes PfCRT K76T as necessary but not sufficient for chloroquine resistance, and describes PfMDR1 N86Y as modulatory rather than independent. That evidence context is not, by itself, an application rule. This installation starts with no active scientific rules; until reviewed rules are approved and enabled, results remain indeterminate.</p>
                    <p class="max-w-3xl leading-7 text-zinc-400">An assessment is not a clinical diagnosis, a treatment recommendation, or evidence that a particular therapy will succeed or fail. Interpret findings with qualified scientific and clinical expertise.</p>
                </div>
                <aside class="flex flex-col gap-4 rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
                    <h3 class="font-semibold">Reference starting point</h3>
                    <a class="text-sm leading-6 text-indigo-200 underline decoration-indigo-300/50 underline-offset-4 hover:text-white" href="https://www.who.int/tools/compendium-of-molecular-markers-for-antimalarial-drug-resistance" target="_blank" rel="noopener noreferrer">WHO Compendium of Molecular Markers for Antimalarial Drug Resistance</a>
                    <a class="text-sm leading-6 text-indigo-200 underline decoration-indigo-300/50 underline-offset-4 hover:text-white" href="https://www.who.int/tools/compendium-of-molecular-markers-for-antimalarial-drug-resistance/results" target="_blank" rel="noopener noreferrer">WHO compendium results, including chloroquine</a>
                    <p class="text-xs leading-5 text-zinc-500">Consult current source material and the relevant dataset documentation before approving any rule. A citation in the catalogue does not imply that a rule has been independently validated.</p>
                </aside>
            </section>
        </main>

        <footer class="border-t border-zinc-800">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-5 py-8 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <p>Antimalaria Research · Rule-based marker assessment</p>
                <p>Academic and decision-support use only · Not a clinical diagnostic system</p>
            </div>
        </footer>
    </body>
</html>
