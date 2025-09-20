(function($) {
    'use strict';

    // Debug: Confirm script is loading
    console.log('PLDB Admin Scripts Loaded');

    // Helper function to compare values, treating numeric strings as equal to numbers
    function valuesAreDifferent(original, current) {
        const origStr = String(original || '').trim();
        const currStr = String(current || '').trim();

        if (!isNaN(origStr) && !isNaN(currStr) && origStr !== '' && currStr !== '') {
            return parseFloat(origStr) !== parseFloat(currStr);
        }

        return origStr !== currStr;
    }

    // Helper to escape HTML for safe insertion
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Wait for document ready
    $(document).ready(function() {
        console.log('PLDB: Document Ready');
        console.log('PLDB: Form exists?', $('#pldb-edit-show-form').length);

        // Show confirmation dialog for show/track changes
        $(document).on('submit', '#pldb-edit-show-form', function(e) {
            console.log('PLDB: Form submit intercepted');

            const changes = [];
            const artistWarnings = [];

            // Check for deleted plays
            const deletedPlays = $('input[name="delete_plays[]"]:checked').length;
            if (deletedPlays > 0) {
                const deletedTracks = [];
                $('input[name="delete_plays[]"]:checked').each(function() {
                    const row = $(this).closest('tr');
                    const track = escapeHtml(row.find('.track-title-input').val());
                    const artist = escapeHtml(row.find('.artist-input').val());
                    deletedTracks.push('  - "' + track + '" by ' + artist);
                });
                changes.push('TRACKS TO BE REMOVED FROM THIS SHOW:\n' + deletedTracks.join('\n'));
            }

            // Check for track title changes
            const trackChanges = [];
            $('.track-title-input').each(function() {
                const original = $(this).data('original');
                const current = $(this).val();
                if (valuesAreDifferent(original, current)) {
                    const row = $(this).closest('tr');
                    const artist = escapeHtml(row.find('.artist-input').val());
                    trackChanges.push('  - "' + escapeHtml(original) + '" → "' + escapeHtml(current) + '" (by ' + artist + ')');
                }
            });

            if (trackChanges.length > 0) {
                changes.push('TRACK TITLES TO BE UPDATED:\n' + trackChanges.join('\n') +
                            '\n(This affects ALL shows where these tracks appear)');
            }

            // Check for artist changes
            const artistChanges = [];

            $('.artist-input').each(function() {
                const original = $(this).data('original');
                const current = $(this).val();
                if (valuesAreDifferent(original, current)) {
                    const row = $(this).closest('tr');
                    const track = escapeHtml(row.find('.track-title-input').val());
                    const totalTracks = parseInt($(this).data('total-tracks')) || 0;

                    artistChanges.push('  - "' + track + '": "' + escapeHtml(original) + '" → "' + escapeHtml(current) + '"');

                    if (totalTracks === 1) {
                        artistWarnings.push('  ⚠️  Artist "' + escapeHtml(original) + '" will be DELETED (last track reassigned)');
                    }
                }
            });

            if (artistChanges.length > 0) {
                let artistSection = 'ARTISTS TO BE CHANGED:\n' + artistChanges.join('\n') +
                            '\n(Will merge with existing artist or create new one)';
                if (artistWarnings.length > 0) {
                    artistSection += '\n\n' + artistWarnings.join('\n');
                }
                changes.push(artistSection);
            }

            // Check for suggester changes
            const suggesterChanges = [];
            $('.suggester-input').each(function() {
                const original = $(this).data('original') || '';
                const current = $(this).val() || '';
                if (valuesAreDifferent(original, current)) {
                    const row = $(this).closest('tr');
                    const track = escapeHtml(row.find('.track-title-input').val());
                    const artist = escapeHtml(row.find('.artist-input').val());
                    suggesterChanges.push('  - "' + track + '" by ' + artist + ':\n' +
                                        '    FROM: ' + (original || '(empty)') + '\n' +
                                        '    TO: ' + (current || '(empty)'));
                }
            });

            if (suggesterChanges.length > 0) {
                changes.push('SUGGESTERS TO BE UPDATED:\n' + suggesterChanges.join('\n'));
            }

            // Check for comment changes
            const commentChanges = [];
            $('.comment-textarea').each(function() {
                const original = $(this).data('original') || '';
                const current = $(this).val() || '';
                if (valuesAreDifferent(original, current)) {
                    const row = $(this).closest('tr');
                    const track = escapeHtml(row.find('.track-title-input').val());
                    const artist = escapeHtml(row.find('.artist-input').val());
                    commentChanges.push('  - "' + track + '" by ' + artist + ':\n' +
                                      '    FROM: ' + (original || '(empty)') + '\n' +
                                      '    TO: ' + (current || '(empty)'));
                }
            });

            if (commentChanges.length > 0) {
                changes.push('COMMENTS TO BE UPDATED:\n' + commentChanges.join('\n'));
            }

            console.log('PLDB: Changes detected:', changes.length);

            if (changes.length > 0) {
                const message = 'The following changes will be made:\n\n' +
                              changes.join('\n\n') +
                              '\n\n═══════════════════════════════════\n\nContinue with these changes?';
                if (!confirm(message)) {
                    console.log('PLDB: User cancelled');
                    e.preventDefault();
                    return false;
                }
            }

            console.log('PLDB: Allowing form submit');
        });

        // Select all tracks checkbox
        $('#select-all-tracks').on('change', function() {
            $('.move-track-checkbox').prop('checked', $(this).is(':checked'));
        });
    });

})(jQuery);
