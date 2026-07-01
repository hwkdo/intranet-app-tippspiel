<x-intranet-app-tippspiel::tippspiel-layout
    heading="Einstellungen"
    subheading="Punkte-Regeln und KI-Provider konfigurieren"
>
    <div class="glass-card p-6">
        <div class="space-y-6">
            {{-- Standard-Punkteregeln --}}
            <div>
                <flux:heading size="sm" class="mb-3">Standard-Punkteregeln für neue Saisons</flux:heading>
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Exaktes Ergebnis</flux:label>
                        <flux:input type="number" wire:model="defaultPointsExactResult" min="0" max="10" />
                        <flux:description>z. B. Tipp: 2:1 | Ergebnis: 2:1</flux:description>
                    </flux:field>
                    <flux:field>
                        <flux:label>Richtige Tordifferenz</flux:label>
                        <flux:input type="number" wire:model="defaultPointsCorrectDifference" min="0" max="10" />
                        <flux:description>z. B. Tipp: 2:1 | Ergebnis: 3:2</flux:description>
                    </flux:field>
                    <flux:field>
                        <flux:label>Richtige Tendenz</flux:label>
                        <flux:input type="number" wire:model="defaultPointsCorrectTendency" min="0" max="10" />
                        <flux:description>z. B. Tipp: 2:1 | Ergebnis: 1:0</flux:description>
                    </flux:field>
                </div>
            </div>

            <flux:separator />

            {{-- KI-News --}}
            <div>
                <flux:heading size="sm" class="mb-3">KI-Newsartikel nach Spieltag</flux:heading>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>News automatisch nach Spieltagende erstellen</flux:label>
                        <flux:switch wire:model="aiNewsAutoCreateAfterMatchday" />
                        <flux:description>Erstellt nach jedem vollständig abgeschlossenen Spieltag automatisch einen News-Entwurf</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>News automatisch veröffentlichen</flux:label>
                        <flux:switch wire:model="aiNewsAutoPublish" />
                        <flux:description>Bei Aus: News wird als Entwurf angelegt und muss manuell freigegeben werden</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>KI-Provider</flux:label>
                        <flux:select wire:model="aiNewsProvider">
                            <flux:select.option value="langdock">Langdock</flux:select.option>
                            <flux:select.option value="openwebui">OpenWebUI</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Modell</flux:label>
                        <flux:input wire:model="aiNewsModel" placeholder="z. B. gpt-4o (empfohlen) oder gpt-5-mini" />
                        <flux:description>
                            Empfohlen: gpt-4o. Leer = gpt-4o. Reasoning-Modelle (gpt-5.x) nutzen minimalen Reasoning-Aufwand mit Fallback auf gpt-4o.
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Kategorie</flux:label>
                        <flux:select
                            wire:model="aiNewsKategorieId"
                            variant="listbox"
                            searchable
                            placeholder="Kategorie wählen"
                            clearable
                        >
                            @foreach ($kategorien as $id => $name)
                                <flux:select.option :value="$id">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Publisher</flux:label>
                        <flux:select
                            wire:model="aiNewsPublisherId"
                            variant="listbox"
                            searchable
                            placeholder="Autor wählen"
                            clearable
                        >
                            @foreach ($publishers as $id => $name)
                                <flux:select.option :value="$id">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:description>User, der als Autor der generierten News erscheint</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Prompt-Vorlage</flux:label>
                        <flux:textarea
                            wire:model="aiNewsPrompt"
                            rows="12"
                            placeholder="Leer lassen für Standard-Prompt"
                        />
                        <flux:description>
                            @if ($usesCustomPrompt)
                                Es wird deine angepasste Vorlage verwendet.
                            @else
                                Aktuell aktiv: <strong>Standard-Prompt</strong> (Feld leer).
                            @endif
                            Platzhalter:
                            <code>{matchday}</code>,
                            <code>{season_name}</code>,
                            <code>{match_results}</code>,
                            <code>{round_highlights}</code>,
                            <code>{match_tip_analysis}</code>,
                            <code>{leaderboard_changes}</code>,
                            <code>{current_leaderboard}</code>,
                            <code>{storylines}</code>
                        </flux:description>
                    </flux:field>

                    @if ($showDefaultPrompt)
                        <flux:field>
                            <flux:label>Standard-Prompt (Systemvorlage)</flux:label>
                            <flux:textarea
                                readonly
                                rows="16"
                                class="font-mono text-sm"
                            >{{ $defaultPrompt }}</flux:textarea>
                            <flux:description>
                                Diese Vorlage wird verwendet, solange das Feld „Prompt-Vorlage“ leer ist.
                            </flux:description>
                        </flux:field>
                    @endif

                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="toggleDefaultPrompt">
                            {{ $showDefaultPrompt ? 'Standard-Prompt ausblenden' : 'Standard-Prompt anzeigen' }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="resetPrompt">
                            Eigene Vorlage löschen
                        </flux:button>
                        <flux:button size="sm" variant="outline" wire:click="openPromptPreviewModal">
                            Artikel-Prompt-Vorschau
                        </flux:button>
                    </div>

                    <flux:separator />

                    <flux:heading size="sm">KI-Titelbild</flux:heading>

                    <flux:field>
                        <flux:label>Titelbild automatisch generieren</flux:label>
                        <flux:switch wire:model="aiNewsImageAutoGenerate" />
                        <flux:description>
                            Erstellt nach der News-Generierung ein KI-Titelbild mit Saison, Spieltag und Wappen der Top-Begegnungen
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Bild-Modell</flux:label>
                        <flux:input wire:model="aiNewsImageModel" placeholder="z. B. gpt-image-1 oder dall-e-3" />
                        <flux:description>
                            Standard: dall-e-3. Mit Wappen werden echte Team-Logos nach der Generierung per GD eingeblendet.
                            Langdock wird über die OpenAI-kompatible images/generations API angesprochen.
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Prompt-Vorlage Titelbild</flux:label>
                        <flux:textarea
                            wire:model="aiNewsImagePrompt"
                            rows="10"
                            placeholder="Leer lassen für Standard-Prompt"
                        />
                        <flux:description>
                            @if ($usesCustomImagePrompt)
                                Es wird deine angepasste Bild-Vorlage verwendet.
                            @else
                                Aktuell aktiv: <strong>Standard-Bild-Prompt</strong> (Feld leer).
                            @endif
                            Platzhalter:
                            <code>{season_name}</code>,
                            <code>{matchday}</code>,
                            <code>{featured_matches}</code>,
                            <code>{team_names}</code>
                        </flux:description>
                    </flux:field>

                    @if ($showDefaultImagePrompt)
                        <flux:field>
                            <flux:label>Standard-Prompt Titelbild (Systemvorlage)</flux:label>
                            <flux:textarea
                                readonly
                                rows="14"
                                class="font-mono text-sm"
                            >{{ $defaultImagePrompt }}</flux:textarea>
                        </flux:field>
                    @endif

                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="toggleDefaultImagePrompt">
                            {{ $showDefaultImagePrompt ? 'Standard-Bild-Prompt ausblenden' : 'Standard-Bild-Prompt anzeigen' }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="resetImagePrompt">
                            Eigene Bild-Vorlage löschen
                        </flux:button>
                        <flux:button size="sm" variant="outline" wire:click="openPromptPreviewModal">
                            Bild-Prompt-Vorschau
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <flux:button variant="primary" wire:click="save">Einstellungen speichern</flux:button>
        </div>
    </div>

    <flux:modal wire:model="showPromptPreviewModal" class="max-w-4xl">
        <flux:heading>Prompt-Vorschau</flux:heading>
        <flux:text class="mb-4 text-zinc-500">
            Zeigt den fertigen Prompt mit echten Tippspiel-Daten — ohne KI-Aufruf.
            Verwendet die aktuelle Eingabe im Feld „Prompt-Vorlage“ (auch vor dem Speichern).
        </flux:text>

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Saison</flux:label>
                    <flux:select wire:model.live="promptPreviewSeasonId">
                        @foreach ($seasons as $season)
                            <flux:select.option :value="$season->id">
                                {{ $season->name }}{{ $season->is_active ? ' (aktiv)' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Spieltag</flux:label>
                    <flux:select wire:model="promptPreviewMatchday">
                        @forelse ($previewMatchdays as $matchday)
                            <flux:select.option :value="$matchday">{{ $matchday }}. Spieltag</flux:select.option>
                        @empty
                            <flux:select.option value="" disabled>Keine abgeschlossenen Spieltage</flux:select.option>
                        @endforelse
                    </flux:select>
                </flux:field>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <flux:button
                    variant="outline"
                    wire:click="generatePromptPreview"
                    wire:loading.attr="disabled"
                    :disabled="empty($previewMatchdays)"
                >
                    <span wire:loading.remove wire:target="generatePromptPreview">Artikel-Prompt</span>
                    <span wire:loading wire:target="generatePromptPreview">Wird erstellt…</span>
                </flux:button>
                <flux:button
                    variant="outline"
                    wire:click="generateImagePromptPreview"
                    wire:loading.attr="disabled"
                    :disabled="empty($previewMatchdays)"
                >
                    <span wire:loading.remove wire:target="generateImagePromptPreview">Bild-Prompt</span>
                    <span wire:loading wire:target="generateImagePromptPreview">Wird erstellt…</span>
                </flux:button>
            </div>

            @if ($promptPreviewError)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    {{ $promptPreviewError }}
                </flux:callout>
            @endif

            @if ($promptPreviewText)
                <flux:field>
                    <flux:label>Generierter Prompt</flux:label>
                    <flux:textarea
                        readonly
                        rows="20"
                        class="font-mono text-sm"
                    >{{ $promptPreviewText }}</flux:textarea>
                </flux:field>
            @endif
        </div>

        <div class="mt-4 flex justify-end">
            <flux:button variant="ghost" wire:click="$set('showPromptPreviewModal', false)">Schließen</flux:button>
        </div>
    </flux:modal>
</x-intranet-app-tippspiel::tippspiel-layout>
