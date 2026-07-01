<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Support;

use Hwkdo\IntranetAppTippspiel\Data\MatchdayNewsContext;

final class MatchdayNewsPromptBuilder
{
    public const DEFAULT_PROMPT = <<<'PROMPT'
        Du bist Sportredakteur eines internen Firmen-Tippspiels. Schreibe einen Artikel im Stil einer Spieltags-Berichterstattung (400–600 Wörter) über den abgeschlossenen {matchday}. Spieltag der Saison "{season_name}".

        Nutze ausschließlich die folgenden Fakten. Erfinde keine Namen, Ergebnisse oder Ranglistenpositionen.

        === Spielergebnisse und Tipp-Performance pro Spiel ===
        {match_results}

        === Highlights des Spieltags (Einzelwertung) ===
        {round_highlights}

        === Tipp-Schwierigkeit der Spiele ===
        {match_tip_analysis}

        === Entwicklung der Gesamtrangliste ===
        {leaderboard_changes}

        === Aktuelle Gesamtrangliste (Top 10) ===
        {current_leaderboard}

        === Redaktionelle Storylines (als Anregung) ===
        {storylines}

        Der Artikel soll:
        - Auf Deutsch verfasst sein
        - Besonderheiten dieses Spieltags herausarbeiten (Helden, Pleiten, leichte/schwere Spiele)
        - Die Ranglisten-Entwicklung journalistisch einordnen (Führungswechsel, Auf- und Absteiger)
        - Einen lebendigen, sportjournalistischen Ton haben
        - Keinen HTML-Code enthalten, nur Fließtext mit Absätzen
        - Mit einer passenden Überschrift beginnen (als erste Zeile, ohne Markdown-Formatierung)
        PROMPT;

    /** @var list<string> */
    public const PLACEHOLDERS = [
        '{matchday}',
        '{season_name}',
        '{match_results}',
        '{round_highlights}',
        '{match_tip_analysis}',
        '{leaderboard_changes}',
        '{current_leaderboard}',
        '{storylines}',
        '{leaderboard}',
    ];

    public function build(MatchdayNewsContext $context, ?string $template = null): string
    {
        $template = filled($template) ? $template : self::DEFAULT_PROMPT;

        $replacements = [
            '{matchday}' => (string) $context->matchday,
            '{season_name}' => $context->seasonName,
            '{match_results}' => $this->formatMatchResults($context->matches),
            '{round_highlights}' => $this->formatRoundHighlights($context->roundHighlights),
            '{match_tip_analysis}' => $this->formatTipAnalysis($context->tipAnalysis),
            '{leaderboard_changes}' => $this->formatLeaderboardChanges($context->rankChanges, $context->isFirstMatchday),
            '{current_leaderboard}' => $this->formatLeaderboard($context->currentLeaderboard),
            '{storylines}' => $this->formatStorylines($context->storylines),
            '{leaderboard}' => $this->formatLeaderboard($context->currentLeaderboard),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * @param  array<int, array{home: string, away: string, homeScore: int, awayScore: int, tipCount: int, averagePoints: float, exactTips: int, zeroTips: int, topTipper: string|null, topPoints: int}>  $matches
     */
    private function formatMatchResults(array $matches): string
    {
        if ($matches === []) {
            return 'Keine Ergebnisse verfügbar.';
        }

        return collect($matches)
            ->map(function (array $match) {
                $line = "- {$match['home']} {$match['homeScore']}:{$match['awayScore']} {$match['away']}";
                $line .= " | {$match['tipCount']} Tipps, Ø {$match['averagePoints']} Pkt.";
                $line .= ", {$match['exactTips']} exakt, {$match['zeroTips']} mit 0 Pkt.";
                if ($match['topTipper']) {
                    $line .= " | Bester Tipper: {$match['topTipper']} ({$match['topPoints']} Pkt.)";
                }

                return $line;
            })
            ->implode("\n");
    }

    /**
     * @param  array{
     *     topScorers: list<array{user_name: string, round_points: int}>,
     *     lowScorers: list<array{user_name: string, round_points: int}>,
     *     zeroScorers: list<string>,
     *     averageRoundPoints: float,
     *     participantCount: int,
     * }  $highlights
     */
    private function formatRoundHighlights(array $highlights): string
    {
        if ($highlights['participantCount'] === 0) {
            return 'Keine Spieltagswertung verfügbar.';
        }

        $lines = [
            "Teilnehmer mit Tipp: {$highlights['participantCount']}",
            "Durchschnittliche Spieltagspunkte: {$highlights['averageRoundPoints']}",
        ];

        if ($highlights['topScorers'] !== []) {
            $top = collect($highlights['topScorers'])
                ->map(fn (array $e) => "{$e['user_name']} ({$e['round_points']} Pkt.)")
                ->implode(', ');
            $lines[] = "Beste Spieltagsleistung: {$top}";
        }

        if ($highlights['lowScorers'] !== []) {
            $low = collect($highlights['lowScorers'])
                ->map(fn (array $e) => "{$e['user_name']} ({$e['round_points']} Pkt.)")
                ->implode(', ');
            $lines[] = "Schwächste Spieltagsleistung: {$low}";
        }

        if ($highlights['zeroScorers'] !== []) {
            $lines[] = 'Ohne Punkte: '.implode(', ', $highlights['zeroScorers']);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array{
     *     easiestMatch: array{label: string, averagePoints: float, exactTips: int, tipCount: int}|null,
     *     hardestMatch: array{label: string, averagePoints: float, zeroTips: int, tipCount: int}|null,
     *     matches: list<array{label: string, averagePoints: float, exactTips: int, zeroTips: int, tipCount: int}>,
     * }  $analysis
     */
    private function formatTipAnalysis(array $analysis): string
    {
        $lines = [];

        if ($analysis['easiestMatch'] !== null) {
            $m = $analysis['easiestMatch'];
            $lines[] = "Leichtestes Spiel: {$m['label']} — Ø {$m['averagePoints']} Pkt., {$m['exactTips']} exakte Tipps bei {$m['tipCount']} Teilnehmern";
        }

        if ($analysis['hardestMatch'] !== null) {
            $m = $analysis['hardestMatch'];
            $lines[] = "Schwierigstes Spiel: {$m['label']} — Ø {$m['averagePoints']} Pkt., {$m['zeroTips']} Nullpunkte-Tipps bei {$m['tipCount']} Teilnehmern";
        }

        foreach ($analysis['matches'] as $match) {
            $lines[] = "- {$match['label']}: Ø {$match['averagePoints']} Pkt., {$match['exactTips']} exakt, {$match['zeroTips']} null";
        }

        return $lines !== [] ? implode("\n", $lines) : 'Keine Tipp-Analyse verfügbar.';
    }

    /**
     * @param  array{
     *     hasComparison: bool,
     *     newLeader: array{user_name: string, previous_rank: int|null}|null,
     *     previousLeader: array{user_name: string, current_rank: int}|null,
     *     unchangedLeader: string|null,
     *     biggestClimber: array{user_name: string, rank_change: int, current_rank: int, previous_rank: int}|null,
     *     biggestFaller: array{user_name: string, rank_change: int, current_rank: int, previous_rank: int}|null,
     *     changes: list<array{user_name: string, current_rank: int, previous_rank: int|null, rank_change: int|null, total_points: int}>,
     * }  $rankChanges
     */
    private function formatLeaderboardChanges(array $rankChanges, bool $isFirstMatchday): string
    {
        if ($isFirstMatchday) {
            return 'Erster Spieltag — es gibt noch keinen Vergleich zur vorherigen Gesamtrangliste.';
        }

        if (! $rankChanges['hasComparison']) {
            return 'Keine Vergleichsdaten zur vorherigen Rangliste verfügbar.';
        }

        $lines = [];

        if ($rankChanges['newLeader'] !== null) {
            $leader = $rankChanges['newLeader'];
            $prev = $leader['previous_rank'] !== null ? " (kam von Platz {$leader['previous_rank']})" : '';
            $lines[] = "Neuer Tabellenführer: {$leader['user_name']}{$prev}";
        } elseif ($rankChanges['unchangedLeader'] !== null) {
            $lines[] = "Tabellenführer unverändert: {$rankChanges['unchangedLeader']}";
        }

        if ($rankChanges['previousLeader'] !== null) {
            $former = $rankChanges['previousLeader'];
            $lines[] = "Bisheriger Führender jetzt auf Platz {$former['current_rank']}: {$former['user_name']}";
        }

        if ($rankChanges['biggestClimber'] !== null) {
            $c = $rankChanges['biggestClimber'];
            $lines[] = "Größter Aufsteiger: {$c['user_name']} (Platz {$c['previous_rank']} → {$c['current_rank']})";
        }

        if ($rankChanges['biggestFaller'] !== null) {
            $f = $rankChanges['biggestFaller'];
            $lines[] = "Größter Absteiger: {$f['user_name']} (Platz {$f['previous_rank']} → {$f['current_rank']})";
        }

        $significantChanges = collect($rankChanges['changes'])
            ->filter(fn (array $c) => $c['rank_change'] !== null && abs($c['rank_change']) >= 2)
            ->take(5);

        foreach ($significantChanges as $change) {
            $direction = $change['rank_change'] > 0 ? '↑' : '↓';
            $lines[] = "{$change['user_name']}: Platz {$change['previous_rank']} → {$change['current_rank']} ({$direction}".abs($change['rank_change']).')';
        }

        return $lines !== [] ? implode("\n", $lines) : 'Keine nennenswerten Ranglistenänderungen.';
    }

    /**
     * @param  array<int, array{rank: int, user_name: string, total_points: int}>  $leaderboard
     */
    private function formatLeaderboard(array $leaderboard): string
    {
        if ($leaderboard === []) {
            return 'Noch keine Ranglistendaten verfügbar.';
        }

        return collect(array_slice($leaderboard, 0, 10))
            ->map(fn (array $entry) => "{$entry['rank']}. {$entry['user_name']} — {$entry['total_points']} Punkte")
            ->implode("\n");
    }

    /**
     * @param  list<string>  $storylines
     */
    private function formatStorylines(array $storylines): string
    {
        if ($storylines === []) {
            return 'Keine zusätzlichen Storylines.';
        }

        return collect($storylines)
            ->map(fn (string $line) => "- {$line}")
            ->implode("\n");
    }
}
